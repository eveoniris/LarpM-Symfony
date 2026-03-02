<?php

declare(strict_types=1);

namespace App\Repository;

/**
 * LarpManager\Repository\LieuRepository.
 *
 * @author kevin
 */
class LieuRepository extends BaseRepository
{
    /**
     * Trouve tous les lieux classé par ordre alphabétique.
     */
    /** @return array<int, \App\Entity\Lieu> */
    public function findAllOrderedByNom(): array
    {
        return $this->getEntityManager()->createQuery('SELECT l FROM App\Entity\Lieu l ORDER BY l.nom ASC')->getResult();
    }
}
