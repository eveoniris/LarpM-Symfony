<?php

declare(strict_types=1);

namespace App\Repository;

/**
 * LarpManager\Repository\GroupeGnOrdreRepository.
 *
 * @author Kevin F.
 */
class GroupeGnOrdreRepository extends BaseRepository
{
    /**
     * Trouve un groupe en fonction de son code.
     */
    /** @return array<int, \App\Entity\GroupeGnOrdre> */
    public function findByGn(int $gnId): array
    {
        return $this
            ->getEntityManager()
            ->createQuery('SELECT g FROM App\Entity\GroupeGnOrdre g JOIN g.groupeGn ggn JOIN g.gn gn WHERE gn.id = :gnId')
            ->setParameter('gnId', $gnId)
            ->getResult();
    }
}
