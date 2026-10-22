<?php

declare(strict_types=1);

namespace App\Tests\Integration\Command;

use App\Entity\Depouillement;
use App\Enum\DepouillementType;
use App\Tests\Factory\GnFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Commande app:depouillement:purger-simulations : supprime les simulations, jamais les dépouillements réels.
 */
#[Group('integration')]
class PurgerSimulationsDepouillementTest extends KernelTestCase
{
    private function commande(): CommandTester
    {
        self::bootKernel();

        return new CommandTester((new Application(self::$kernel))->find('app:depouillement:purger-simulations'));
    }

    private function creer(EntityManagerInterface $em, $gn, bool $simulation): void
    {
        $em->persist(
            (new Depouillement())
                ->setGn($gn)
                ->setType(DepouillementType::JOUEUR)
                ->setNumero('1')
                ->setSimulation($simulation),
        );
    }

    public function testPurgeToutesLesSimulationsEtConserveLeReel(): void
    {
        $commande = $this->commande();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $gn = GnFactory::createOne();
        $this->creer($em, $gn, true);
        $this->creer($em, $gn, true);
        $this->creer($em, $gn, false);
        $em->flush();

        $commande->execute([]);

        $commande->assertCommandIsSuccessful();
        static::assertStringContainsString('2 simulation(s) supprimée(s)', $commande->getDisplay());
        $em->clear();
        $restants = $em->getRepository(Depouillement::class)->findAll();
        static::assertCount(1, $restants);
        static::assertFalse($restants[0]->isSimulation());
    }

    public function testOptionGnLimiteLaPurge(): void
    {
        $commande = $this->commande();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $gn = GnFactory::createOne();
        $autreGn = GnFactory::createOne();
        $this->creer($em, $gn, true);
        $this->creer($em, $autreGn, true);
        $em->flush();

        $commande->execute(['--gn' => (string) $gn->getId()]);

        $commande->assertCommandIsSuccessful();
        $em->clear();
        static::assertCount(1, $em->getRepository(Depouillement::class)->findAll());
    }

    public function testGnInconnuEchoue(): void
    {
        $commande = $this->commande();

        $commande->execute(['--gn' => '999999999']);

        static::assertSame(1, $commande->getStatusCode());
    }
}
