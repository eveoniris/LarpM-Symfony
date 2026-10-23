<?php

declare(strict_types=1);

namespace App\Tests\Integration\Entity;

use App\Entity\Depouillement;
use App\Entity\DepouillementHistory;
use App\Enum\DepouillementStatut;
use App\Enum\DepouillementType;
use App\Repository\DepouillementRepository;
use App\Service\DepouillementService;
use App\Tests\Factory\GnFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Entité Depouillement : persistance, historique, recherche d'un dépouillement réel et purge des simulations.
 *
 * DAMA enveloppe chaque test dans une transaction annulée automatiquement.
 */
#[Group('integration')]
class DepouillementTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    private DepouillementRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->repository = $this->em->getRepository(Depouillement::class);
    }

    private function creer(DepouillementType $type, string $numero, bool $simulation): Depouillement
    {
        $depouillement = (new Depouillement())
            ->setGn(GnFactory::createOne())
            ->setType($type)
            ->setNumero($numero)
            ->setSimulation($simulation);
        $this->em->persist($depouillement);
        $this->em->flush();

        return $depouillement;
    }

    public function testPersistanceAvecValeursParDefaut(): void
    {
        $depouillement = $this->creer(DepouillementType::JOUEUR, '2328', false);
        $id = $depouillement->getId();
        $this->em->clear();

        $recharge = $this->repository->find($id);

        static::assertSame(DepouillementStatut::EN_DEPOUILLEMENT, $recharge->getStatut());
        static::assertSame(DepouillementType::JOUEUR, $recharge->getType());
        static::assertFalse($recharge->isSimulation());
        static::assertSame([], $recharge->getDonnees());
        static::assertTrue($recharge->getStatut()->isEditable());
    }

    public function testHistoriquePersisteEtOrdonne(): void
    {
        $depouillement = $this->creer(DepouillementType::GROUPE, '21', false);
        $depouilleur = UserFactory::createOne();

        $depouillement->addHistory(
            (new DepouillementHistory())
                ->setUser($depouilleur)
                ->setActionType(DepouillementHistory::ACTION_REPRISE)
                ->setStatutAvant(DepouillementStatut::EN_DEPOUILLEMENT)
                ->setStatutApres(DepouillementStatut::A_VALIDER),
        );
        $this->em->flush();
        $id = $depouillement->getId();
        $this->em->clear();

        $recharge = $this->repository->find($id);

        static::assertCount(1, $recharge->getHistories());
        $historique = $recharge->getHistories()->first();
        static::assertSame(DepouillementStatut::A_VALIDER, $historique->getStatutApres());
        static::assertSame($depouilleur->getId(), $historique->getUser()?->getId());
    }

    public function testSupprimerSimulationRefuseUnDepouillementReel(): void
    {
        $service = static::getContainer()->get(DepouillementService::class);
        $reel = $this->creer(DepouillementType::JOUEUR, '7', false);
        $simulation = $this->creer(DepouillementType::JOUEUR, '8', true);
        $idReel = $reel->getId();
        $idSimulation = $simulation->getId();

        static::assertFalse($service->supprimerSimulation($reel));
        static::assertTrue($service->supprimerSimulation($simulation));

        $this->em->clear();
        static::assertNotNull($this->repository->find($idReel));
        static::assertNull($this->repository->find($idSimulation));
    }

    public function testFindReelIgnoreLesSimulations(): void
    {
        $simulation = $this->creer(DepouillementType::JOUEUR, '100', true);
        $reel = $this->creer(DepouillementType::JOUEUR, '100', false);

        static::assertSame($reel->getId(), $this->repository->findByEnveloppe($reel->getGn(), DepouillementType::JOUEUR, '100', simulation: false)?->getId());
        static::assertNull($this->repository->findByEnveloppe($simulation->getGn(), DepouillementType::JOUEUR, '100', simulation: false));
    }

    public function testPurgeSupprimeLesSimulationsEtLeurHistoriqueSeulement(): void
    {
        $simulation = $this->creer(DepouillementType::GROUPE, '5', true);
        $simulation->addHistory((new DepouillementHistory())->setActionType(DepouillementHistory::ACTION_CREATE));
        $reel = $this->creer(DepouillementType::GROUPE, '6', false);
        $this->em->flush();
        $idReel = $reel->getId();
        $idSimulation = $simulation->getId();

        $supprimes = $this->repository->purgeSimulations();
        $this->em->clear();

        static::assertSame(1, $supprimes);
        static::assertNull($this->repository->find($idSimulation));
        static::assertNotNull($this->repository->find($idReel));
        static::assertSame(0, $this->em->getRepository(DepouillementHistory::class)->count(['depouillement' => $idSimulation]));
    }
}
