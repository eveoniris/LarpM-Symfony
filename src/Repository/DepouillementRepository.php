<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Depouillement;
use App\Entity\Gn;
use App\Enum\DepouillementType;

class DepouillementRepository extends BaseRepository
{
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
     * Supprime toutes les simulations (leur historique part en cascade). Retourne le nombre supprimé.
     */
    public function purgeSimulations(): int
    {
        return (int) $this
            ->getEntityManager()
            ->createQuery('DELETE FROM ' . Depouillement::class . ' d WHERE d.simulation = true')
            ->execute();
    }
}
