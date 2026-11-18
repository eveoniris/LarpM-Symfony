<?php

declare(strict_types=1);

namespace App\Tests\Integration\Command;

use App\Entity\LoginHistory;
use App\Enum\LoginMethod;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Commande app:login-history:purger : IP anonymisées après 30 jours, lignes supprimées après 12 mois.
 */
#[Group('integration')]
class PurgerLoginHistoryTest extends KernelTestCase
{
    private function commande(): CommandTester
    {
        self::bootKernel();

        return new CommandTester((new Application(self::$kernel))->find('app:login-history:purger'));
    }

    private function creer(EntityManagerInterface $em, string $identifier, string $ip, string $age): LoginHistory
    {
        $entry = (new LoginHistory(LoginMethod::WEB, true, new DateTimeImmutable($age)))
            ->setIdentifier($identifier)
            ->setIp($ip);
        $em->persist($entry);

        return $entry;
    }

    /** @return array{recent: LoginHistory, moyenne: LoginHistory, ancienne: LoginHistory} */
    private function jeuDeDonnees(EntityManagerInterface $em, string $suffixe): array
    {
        $jeu = [
            'recent' => $this->creer($em, 'recent-' . $suffixe, '203.0.113.9', '-2 days'),
            'moyenne' => $this->creer($em, 'moyenne-' . $suffixe, '198.51.100.77', '-60 days'),
            'ancienne' => $this->creer($em, 'ancienne-' . $suffixe, '192.0.2.5', '-13 months'),
        ];
        $em->flush();

        return $jeu;
    }

    public function testSimulationNeModifieRien(): void
    {
        $commande = $this->commande();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $jeu = $this->jeuDeDonnees($em, 'simu');
        $ids = array_map(static fn (LoginHistory $e): ?int => $e->getId(), $jeu);
        $em->clear();

        $commande->execute([]);

        static::assertStringContainsString('SIMULATION', $commande->getDisplay());
        foreach ($ids as $id) {
            static::assertNotNull($em->find(LoginHistory::class, $id));
        }
        static::assertSame('198.51.100.77', $em->find(LoginHistory::class, $ids['moyenne'])?->getIp());
    }

    public function testApplyAnonymiseLesMoyennesSupprimeLesAnciennesEtGardeLesRecentes(): void
    {
        $commande = $this->commande();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $jeu = $this->jeuDeDonnees($em, 'apply');
        $ids = array_map(static fn (LoginHistory $e): ?int => $e->getId(), $jeu);
        $em->clear();

        $commande->execute(['--apply' => true]);
        $em->clear();

        $recent = $em->find(LoginHistory::class, $ids['recent']);
        static::assertSame('203.0.113.9', $recent?->getIp());
        static::assertFalse($recent->isIpAnonymized());

        $moyenne = $em->find(LoginHistory::class, $ids['moyenne']);
        static::assertSame('198.51.100.0', $moyenne?->getIp());
        static::assertTrue($moyenne->isIpAnonymized());

        static::assertNull($em->find(LoginHistory::class, $ids['ancienne']));
    }

    public function testEstIdempotente(): void
    {
        $commande = $this->commande();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $jeu = $this->jeuDeDonnees($em, 'idem');
        $moyenneId = $jeu['moyenne']->getId();
        $em->clear();

        $commande->execute(['--apply' => true]);
        $commande->execute(['--apply' => true]);
        $em->clear();

        static::assertSame('198.51.100.0', $em->find(LoginHistory::class, $moyenneId)?->getIp());
    }

    public function testRefuseUneDureeInvalide(): void
    {
        $commande = $this->commande();

        $code = $commande->execute(['--anonymiser-apres-jours' => '0']);

        static::assertSame(2, $code);
    }
}
