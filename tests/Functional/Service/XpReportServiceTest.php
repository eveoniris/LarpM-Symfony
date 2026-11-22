<?php

declare(strict_types=1);

namespace App\Tests\Functional\Service;

use App\Entity\ExperienceGain;
use App\Entity\LogAction;
use App\Entity\Personnage;
use App\Entity\XpReport;
use App\Enum\LogActionType;
use App\Service\DeletionLogContext;
use App\Service\PersonnageParticipation;
use App\Service\RetraitXpService;
use App\Service\XpReportCalculateur;
use App\Service\XpReportException;
use App\Service\XpReportService;
use App\Tests\Factory\ExperienceGainFactory;
use App\Tests\Factory\GnFactory;
use App\Tests\Factory\ParticipantFactory;
use App\Tests\Factory\PersonnageFactory;
use App\Tests\Factory\UserFactory;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

// @mago-expect lint:too-many-methods
#[Group('functional')]
#[Group('rulebook')]
final class XpReportServiceTest extends KernelTestCase
{
    private function service(): XpReportService
    {
        self::bootKernel();

        // Service pas encore injecté ailleurs (donc supprimé du conteneur de test) : on l'assemble avec les services partagés.
        $conteneur = static::getContainer();
        $em = $conteneur->get(EntityManagerInterface::class);

        return new XpReportService(
            $em,
            new XpReportCalculateur(),
            $em->getRepository(XpReport::class),
            new PersonnageParticipation($em),
            $conteneur->get(RetraitXpService::class),
            $conteneur->get(DeletionLogContext::class),
        );
    }

    /** Vérifie que l'action est refusée par une XpReportException. */
    private function refuse(callable $action): void
    {
        try {
            $action();
        } catch (XpReportException) {
            $this->addToAssertionCount(1);

            return;
        }

        static::fail('Un refus (XpReportException) était attendu.');
    }

    private function em(): EntityManagerInterface
    {
        return static::getContainer()->get(EntityManagerInterface::class);
    }

    /** Mort avec 8 XP acquis en jeu (création exclue) : tiers 3, moitié 4. */
    private function mort(object $joueur): Personnage
    {
        $mort = PersonnageFactory::createOne(['user' => $joueur, 'vivant' => false, 'xp' => 25]);
        ExperienceGainFactory::createOne(['personnage' => $mort, 'explanation' => XpReportCalculateur::LIBELLE_CREATION, 'xp_gain' => 17]);
        ExperienceGainFactory::createOne(['personnage' => $mort, 'explanation' => 'Participation', 'xp_gain' => 8]);

        return $mort;
    }

    public function testTiersVersUnPersonnageDuMemeJoueur(): void
    {
        $service = $this->service();
        $joueur = UserFactory::createOne();
        $mort = $this->mort($joueur);
        $nouveau = PersonnageFactory::createOne(['user' => $joueur, 'xp' => 10]);

        $report = $service->reporter($mort, $nouveau, XpReportCalculateur::MODE_TIERS, $joueur);

        static::assertSame(3, $report->getXpReporte());
        static::assertSame(8, $report->getXpBase());
        $this->em()->clear();
        $apres = $this->em()->find(Personnage::class, $nouveau->getId());
        static::assertSame(13, $apres->getXp());
        static::assertSame(XpReportCalculateur::PREFIXE_REPORT . $mort->getIdentity(withId: false), $apres->getExperienceGains()->first()->getExplanation());
        static::assertNotEmpty($this->em()->getRepository(LogAction::class)->findBy(['type' => LogActionType::XP_REPORT->value]));
    }

    public function testMoitiePourUnHeritierSansJoueur(): void
    {
        $service = $this->service();
        $mort = $this->mort(UserFactory::createOne());
        $heritier = PersonnageFactory::createOne(['user' => null, 'xp' => 20]);

        $report = $service->reporter($mort, $heritier, XpReportCalculateur::MODE_MOITIE, UserFactory::createOne());

        static::assertSame(4, $report->getXpReporte());
    }

    public function testRefuseUnMortVivant(): void
    {
        $service = $this->service();
        $joueur = UserFactory::createOne();
        $vivant = PersonnageFactory::createOne(['user' => $joueur, 'vivant' => true]);
        $nouveau = PersonnageFactory::createOne(['user' => $joueur]);

        $this->expectException(XpReportException::class);
        $service->reporter($vivant, $nouveau, XpReportCalculateur::MODE_TIERS, $joueur);
    }

    public function testUnSeulReportParMortEtParBeneficiaire(): void
    {
        $service = $this->service();
        $joueur = UserFactory::createOne();
        $mort = $this->mort($joueur);
        $premier = PersonnageFactory::createOne(['user' => $joueur]);
        $second = PersonnageFactory::createOne(['user' => $joueur]);
        $service->reporter($mort, $premier, XpReportCalculateur::MODE_TIERS, $joueur);

        $this->refuse(static fn () => $service->reporter($mort, $second, XpReportCalculateur::MODE_TIERS, $joueur));

        $autreMort = $this->mort($joueur);
        $this->expectException(XpReportException::class);
        $service->reporter($autreMort, $premier, XpReportCalculateur::MODE_TIERS, $joueur);
    }

    public function testTiersRefuseVersUnAutreJoueur(): void
    {
        $service = $this->service();
        $mort = $this->mort(UserFactory::createOne());
        $autre = PersonnageFactory::createOne(['user' => UserFactory::createOne()]);

        $this->expectException(XpReportException::class);
        $service->reporter($mort, $autre, XpReportCalculateur::MODE_TIERS, null);
    }

    public function testParticipationPasseeRefuseeSaufForcageAvecMotif(): void
    {
        $service = $this->service();
        $joueur = UserFactory::createOne();
        $mort = $this->mort($joueur);
        $nouveau = PersonnageFactory::createOne(['user' => $joueur]);
        $gnPasse = GnFactory::createOne(['date_debut' => new DateTime('-1 year')]);
        ParticipantFactory::createOne(['gn' => $gnPasse, 'personnage' => $nouveau, 'user' => $joueur]);

        $this->refuse(static fn () => $service->reporter($mort, $nouveau, XpReportCalculateur::MODE_TIERS, $joueur));
        $this->refuse(static fn () => $service->reporter($mort, $nouveau, XpReportCalculateur::MODE_TIERS, $joueur, forcer: true, motif: '  '));

        $report = $service->reporter($mort, $nouveau, XpReportCalculateur::MODE_TIERS, $joueur, forcer: true, motif: 'Erreur de saisie');
        static::assertTrue($report->isForce());
    }

    public function testGnFuturAutorise(): void
    {
        $service = $this->service();
        $joueur = UserFactory::createOne();
        $mort = $this->mort($joueur);
        $nouveau = PersonnageFactory::createOne(['user' => $joueur]);
        $gnFutur = GnFactory::createOne(['date_debut' => new DateTime('+6 months')]);
        ParticipantFactory::createOne(['gn' => $gnFutur, 'personnage' => $nouveau, 'user' => $joueur]);

        $report = $service->reporter($mort, $nouveau, XpReportCalculateur::MODE_TIERS, $joueur);

        static::assertFalse($report->isForce());
    }

    public function testAnnulationRetireLeBonusEtLibereLeMortEtLeBeneficiaire(): void
    {
        $service = $this->service();
        $joueur = UserFactory::createOne();
        $mort = $this->mort($joueur);
        $nouveau = PersonnageFactory::createOne(['user' => $joueur, 'xp' => 10]);
        $report = $service->reporter($mort, $nouveau, XpReportCalculateur::MODE_TIERS, $joueur);
        $gainId = $report->getExperienceGain()->getId();

        $this->refuse(static fn () => $service->annuler($report, $joueur, ''));

        $service->annuler($report, $joueur, 'Report erroné');

        $this->em()->clear();
        static::assertSame(10, $this->em()->find(Personnage::class, $nouveau->getId())->getXp());
        static::assertTrue($this->em()->find(ExperienceGain::class, $gainId)->isDeleted());
        static::assertCount(0, $this->em()->getRepository(XpReport::class)->findAll());
        static::assertNotEmpty($this->em()->getRepository(LogAction::class)->findBy(['type' => LogActionType::XP_REPORT_ANNULE->value]));

        // Le mort peut de nouveau transmettre.
        $mortRecharge = $this->em()->find(Personnage::class, $mort->getId());
        $nouveauRecharge = $this->em()->find(Personnage::class, $nouveau->getId());
        $service->reporter($mortRecharge, $nouveauRecharge, XpReportCalculateur::MODE_TIERS, $mortRecharge->getUser());
        static::assertCount(1, $this->em()->getRepository(XpReport::class)->findAll());
    }
}
