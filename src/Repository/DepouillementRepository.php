<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Depouillement;
use App\Entity\Gn;
use App\Enum\DepouillementStatut;
use App\Enum\DepouillementType;

class DepouillementRepository extends BaseRepository
{
    /**
     * Le findAll() de BaseRepository trie par label, que cette entité n'a pas.
     *
     * @return array<int, object>
     */
    public function findAll(): array
    {
        return $this->findBy([], ['id' => 'ASC']);
    }

    /**
     * Dépouillement réel (hors simulation) déjà ouvert pour une enveloppe : sert à avertir d'une reprise ou d'un doublon.
     */
    public function findReelByEnveloppe(Gn $gn, DepouillementType $type, string $numero): ?Depouillement
    {
        return $this
            ->getEntityManager()
            ->getRepository(Depouillement::class)
            ->findOneBy([
                'gn' => $gn,
                'type' => $type,
                'numero' => $numero,
                'simulation' => false,
            ]);
    }

    /**
     * Dépouillement déjà ouvert pour cette enveloppe (réel ou simulation), quel que soit son statut.
     */
    public function findByEnveloppe(Gn $gn, DepouillementType $type, string $numero, bool $simulation): ?Depouillement
    {
        return $this
            ->getEntityManager()
            ->getRepository(Depouillement::class)
            ->findOneBy([
                'gn' => $gn,
                'type' => $type,
                'numero' => $numero,
                'simulation' => $simulation,
            ]);
    }

    /**
     * Nombre de dépouillements d'un type encore en cours de saisie pour un GN (réels ou simulations).
     */
    public function countEnCours(Gn $gn, DepouillementType $type, bool $simulation): int
    {
        return (int) $this
            ->getEntityManager()
            ->createQueryBuilder()
            ->select('COUNT(d.id)')
            ->from(Depouillement::class, 'd')
            ->where('d.gn = :gn AND d.type = :type AND d.simulation = :simulation AND d.statut = :statut')
            ->setParameter('gn', $gn)
            ->setParameter('type', $type)
            ->setParameter('simulation', $simulation)
            ->setParameter('statut', DepouillementStatut::EN_DEPOUILLEMENT)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Supprime les simulations (toutes, ou celles d'un GN), leur historique part en cascade. Retourne le nombre supprimé.
     */
    public function purgeSimulations(?Gn $gn = null): int
    {
        $query = $this
            ->getEntityManager()
            ->createQueryBuilder()
            ->delete(Depouillement::class, 'd')
            ->where('d.simulation = true');

        if (null !== $gn) {
            $query->andWhere('d.gn = :gn')->setParameter('gn', $gn);
        }

        return (int) $query->getQuery()->execute();
    }
}
