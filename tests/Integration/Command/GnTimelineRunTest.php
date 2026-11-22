<?php

declare(strict_types=1);

namespace App\Tests\Integration\Command;

use App\Entity\Gn;
use App\Entity\GnTimelineStep;
use App\Enum\GnTimelineStatut;
use App\Enum\GnTimelineType;
use App\Repository\GnTimelineStepRepository;
use App\Service\GnTimeline\GnTimelineExecutor;
use App\Service\GnTimeline\GnTimelineHandlerInterface;
use App\Tests\Factory\GnFactory;
use DateTime;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Cron de la chronologie : sélection des étapes automatiques échues, exécution unique et compte rendu.
 */
#[Group('integration')]
class GnTimelineRunTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    private Gn $gn;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->gn = GnFactory::createOne();
        // le verrouillage d'une étape suit la date de verrouillage du GN
        $this->gn->setDateVerrouillage(new DateTime('-1 day'));
    }

    private function etape(GnTimelineType $type, bool $auto, string $date, ?int $xp = null): GnTimelineStep
    {
        $etape = (new GnTimelineStep())
            ->setGn($this->gn)
            ->setLibelle('Étape ' . $type->value)
            ->setType($type)
            ->setAuto($auto)
            ->setXp($xp)
            ->setDatePrevue(new DateTimeImmutable($date));
        $this->em->persist($etape);
        $this->em->flush();

        return $etape;
    }

    private function executeur(GnTimelineType $type, ?string $erreur = null): GnTimelineExecutor
    {
        $handler = new FauxGnTimelineHandler($type, $erreur);

        return new GnTimelineExecutor([$handler], static::getContainer()->get(Connection::class), $this->em, static::getContainer()->get(GnTimelineStepRepository::class), new NullLogger());
    }

    /** @return list<int> */
    private function idsEchus(GnTimelineExecutor $executeur): array
    {
        $ids = array_map(static fn (GnTimelineStep $e): ?int => $e->getId(), $executeur->echues(new DateTimeImmutable()));

        return array_values(array_filter($ids, static fn (?int $id): bool => null !== $id));
    }

    public function testSeulesLesEtapesAutomatiquesEchuesEtAutoriseesSontSelectionnees(): void
    {
        $due = $this->etape(GnTimelineType::VERROUILLAGE, true, '-1 day');
        $vieillissement = $this->etape(GnTimelineType::VIEILLISSEMENT, true, '-1 day');
        $manuelle = $this->etape(GnTimelineType::XP_PARTICIPATION, false, '-1 day', 3);
        $future = $this->etape(GnTimelineType::XP_PARTICIPATION, true, '+5 days', 3);
        $info = $this->etape(GnTimelineType::INFO, true, '-1 day');

        $ids = $this->idsEchus($this->executeur(GnTimelineType::VERROUILLAGE));

        $this->assertContains($due->getId(), $ids);
        $this->assertContains($vieillissement->getId(), $ids);
        $this->assertNotContains($manuelle->getId(), $ids);
        $this->assertNotContains($future->getId(), $ids);
        $this->assertNotContains($info->getId(), $ids);
    }

    public function testUnVerrouillageSansDateDeGnOuAVenirResteManuel(): void
    {
        $this->gn->setDateVerrouillage(null);
        $sansDate = $this->etape(GnTimelineType::VERROUILLAGE, true, '-1 day');
        $this->assertNull($sansDate->getDateEffective());

        $this->gn->setDateVerrouillage(new DateTime('+3 days'));
        $this->em->flush();

        $ids = $this->idsEchus($this->executeur(GnTimelineType::VERROUILLAGE));
        $this->assertNotContains($sansDate->getId(), $ids, "la date propre de l'étape est ignorée pour un verrouillage");

        $this->gn->setDateVerrouillage(new DateTime('-1 hour'));
        $this->em->flush();
        $this->assertContains($sansDate->getId(), $this->idsEchus($this->executeur(GnTimelineType::VERROUILLAGE)));
    }

    public function testUneEtapeNEstExecuteeQuUneFoisEtLeResultatEstEnregistre(): void
    {
        $etape = $this->etape(GnTimelineType::VERROUILLAGE, true, '-1 day');
        $executeur = $this->executeur(GnTimelineType::VERROUILLAGE);

        $premier = $executeur->executer($etape);
        $second = $executeur->executer($etape);

        $this->assertTrue($premier['ok']);
        $this->assertFalse($second['ok']);
        $this->assertSame(GnTimelineStatut::EXECUTE, $etape->getStatut());
        $this->assertSame('fait', $etape->getResultat());
        $this->assertNotNull($etape->getExecutedAt());
    }

    public function testUnEchecPasseEnErreurEtPeutEtreRelanceAMain(): void
    {
        $etape = $this->etape(GnTimelineType::VERROUILLAGE, true, '-1 day');

        $echec = $this->executeur(GnTimelineType::VERROUILLAGE, 'boum')->executer($etape);
        $this->assertFalse($echec['ok']);
        $this->assertSame(GnTimelineStatut::ERREUR, $etape->getStatut());
        $this->assertSame('boum', $etape->getResultat());

        // Le cron ne relance pas une étape en erreur, mais le bouton manuel le peut
        $this->assertNotContains($etape->getId(), $this->idsEchus($this->executeur(GnTimelineType::VERROUILLAGE)));
        $this->assertTrue($this->executeur(GnTimelineType::VERROUILLAGE)->executer($etape)['ok']);
    }

    public function testLaCommandeEnSimulationNExecuteRien(): void
    {
        $etape = $this->etape(GnTimelineType::VERROUILLAGE, true, '-1 day');
        $application = new Application(self::$kernel);
        $testeur = new CommandTester($application->find('app:gn-timeline:run'));

        $this->assertSame(0, $testeur->execute([]));
        $this->assertStringContainsString('simulation', $testeur->getDisplay());
        $this->assertStringContainsString('#' . $etape->getId(), $testeur->getDisplay());

        $this->em->refresh($etape);
        $this->assertSame(GnTimelineStatut::A_FAIRE, $etape->getStatut());
    }
}
