<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Depouillement;
use App\Entity\DepouillementHistory;
use App\Entity\Gn;
use App\Entity\User;
use App\Enum\DepouillementType;
use App\Repository\DepouillementRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Dépouillement des enveloppes de retour de jeu.
 *
 * Phase de développement : seules les simulations peuvent être créées. Une simulation n'a jamais d'effet sur les données réelles
 * et reste purgeable. Le dépouillement réel arrive avec les étapes suivantes du plan.
 */
readonly class DepouillementService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private DepouillementRepository $depouillementRepository,
    ) {
    }

    public function creerSimulation(Gn $gn, DepouillementType $type, string $numero, User $depouilleur): Depouillement
    {
        $depouillement = (new Depouillement())
            ->setGn($gn)
            ->setType($type)
            ->setNumero(trim($numero))
            ->setSimulation(true)
            ->setDepouilleur($depouilleur);

        $depouillement->addHistory(
            (new DepouillementHistory())
                ->setUser($depouilleur)
                ->setActionType(DepouillementHistory::ACTION_CREATE)
                ->setStatutApres($depouillement->getStatut())
                ->setMotif('Simulation'),
        );

        $this->entityManager->persist($depouillement);
        $this->entityManager->flush();

        return $depouillement;
    }

    /**
     * Supprime les simulations (toutes, ou celles d'un GN). Les dépouillements réels ne sont jamais touchés.
     */
    public function purgerSimulations(?Gn $gn = null): int
    {
        return $this->depouillementRepository->purgeSimulations($gn);
    }

    /**
     * @return list<Depouillement>
     */
    public function listerSimulations(Gn $gn): array
    {
        return $this->depouillementRepository->findBy(['gn' => $gn, 'simulation' => true], ['createdAt' => 'DESC']);
    }
}
