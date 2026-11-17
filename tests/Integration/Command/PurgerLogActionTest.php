<?php

declare(strict_types=1);

namespace App\Tests\Integration\Command;

use App\Entity\LogAction;
use App\Enum\LogActionType;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Commande app:log-action:purger : supprime les logs de plus de 5 ans, jamais le type protégé AGING_CHARACTERS.
 */
#[Group('integration')]
class PurgerLogActionTest extends KernelTestCase
{
    private function commande(): CommandTester
    {
        self::bootKernel();

        return new CommandTester((new Application(self::$kernel))->find('app:log-action:purger'));
    }

    private function creer(EntityManagerInterface $em, LogActionType $type, string $age): LogAction
    {
        $log = (new LogAction())
            ->setType($type)
            ->setDate(new DateTime($age))
            ->setData(['test' => 'purge']);
        $em->persist($log);

        return $log;
    }

    /** @return array{recent: LogAction, limite: LogAction, ancien: LogAction, ancienVieillissement: LogAction} */
    private function jeuDeDonnees(EntityManagerInterface $em): array
    {
        $jeu = [
            'recent' => $this->creer($em, LogActionType::XP_ADD, '-2 days'),
            'limite' => $this->creer($em, LogActionType::XP_ADD, '-4 years'),
            'ancien' => $this->creer($em, LogActionType::XP_ADD, '-6 years'),
            'ancienVieillissement' => $this->creer($em, LogActionType::AGING_CHARACTERS, '-6 years'),
        ];
        $em->flush();

        return $jeu;
    }

    /** @param array<string, LogAction> $jeu @return array<string, int|null> */
    private function ids(array $jeu): array
    {
        return array_map(static fn (LogAction $log): ?int => $log->getId(), $jeu);
    }

    public function testSimulationNeSupprimeRien(): void
    {
        $commande = $this->commande();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $ids = $this->ids($this->jeuDeDonnees($em));
        $em->clear();

        $commande->execute([]);

        static::assertStringContainsString('SIMULATION', $commande->getDisplay());
        foreach ($ids as $id) {
            static::assertNotNull($em->find(LogAction::class, $id));
        }
    }

    public function testApplySupprimeLesAnciensEtGardeLesRecentsEtLeTypeProtege(): void
    {
        $commande = $this->commande();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $ids = $this->ids($this->jeuDeDonnees($em));
        $em->clear();

        $commande->execute(['--apply' => true]);
        $em->clear();

        static::assertNotNull($em->find(LogAction::class, $ids['recent']));
        static::assertNotNull($em->find(LogAction::class, $ids['limite']), 'Un log de 4 ans reste sous le seuil de 5 ans.');
        static::assertNull($em->find(LogAction::class, $ids['ancien']));
        static::assertNotNull($em->find(LogAction::class, $ids['ancienVieillissement']), 'La garde anti double vieillissement ne doit jamais être purgée.');
    }

    public function testLaDureeEstConfigurable(): void
    {
        $commande = $this->commande();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $ids = $this->ids($this->jeuDeDonnees($em));
        $em->clear();

        $commande->execute(['--apply' => true, '--supprimer-apres-annees' => '3']);
        $em->clear();

        static::assertNull($em->find(LogAction::class, $ids['limite']));
        static::assertNotNull($em->find(LogAction::class, $ids['recent']));
    }

    public function testRefuseUneDureeInvalide(): void
    {
        $commande = $this->commande();

        static::assertSame(2, $commande->execute(['--supprimer-apres-annees' => '0']));
    }
}
