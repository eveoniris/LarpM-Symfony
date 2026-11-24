<?php

declare(strict_types=1);

namespace App\Tests\Integration\Command;

use App\Entity\IpBloquee;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Commande app:ip-bloquee:purger : suppression des blocages expirés depuis plus de 30 jours.
 */
#[Group('integration')]
class PurgerIpBloqueeTest extends KernelTestCase
{
    private function commande(): CommandTester
    {
        self::bootKernel();

        return new CommandTester((new Application(self::$kernel))->find('app:ip-bloquee:purger'));
    }

    /** @return array{actif: IpBloquee, recemmentExpire: IpBloquee, ancien: IpBloquee} */
    private function jeu(EntityManagerInterface $em): array
    {
        $em->createQuery('DELETE FROM App\Entity\IpBloquee')->execute();
        $jeu = [
            'actif' => new IpBloquee('45.33.40.1/32', new DateTimeImmutable('+3 days')),
            'recemmentExpire' => new IpBloquee('45.33.40.2/32', new DateTimeImmutable('-5 days')),
            'ancien' => new IpBloquee('45.33.40.3/32', new DateTimeImmutable('-45 days')),
        ];
        foreach ($jeu as $entry) {
            $em->persist($entry);
        }
        $em->flush();

        return $jeu;
    }

    public function testSimulationNeSupprimeRien(): void
    {
        $commande = $this->commande();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $this->jeu($em);

        $commande->execute([]);

        static::assertStringContainsString('1 blocage(s) seraient supprimés', $commande->getDisplay());
        static::assertSame(3, $em->getRepository(IpBloquee::class)->count([]));
        $em->createQuery('DELETE FROM App\Entity\IpBloquee')->execute();
    }

    public function testApplySupprimeSeulementLesAnciens(): void
    {
        $commande = $this->commande();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $this->jeu($em);

        $commande->execute(['--apply' => true]);

        $restants = array_map(static fn (IpBloquee $e): string => $e->getCidr(), $em->getRepository(IpBloquee::class)->findAll());
        sort($restants);
        static::assertSame(['45.33.40.1/32', '45.33.40.2/32'], $restants);
        $em->createQuery('DELETE FROM App\Entity\IpBloquee')->execute();
    }
}
