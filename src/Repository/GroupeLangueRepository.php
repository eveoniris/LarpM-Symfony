<?php

declare(strict_types=1);

namespace App\Repository;

/**
 * LarpManager\Repository\GroupeLangueRepository.
 *
 * @author kevin
 */
class GroupeLangueRepository extends BaseRepository
{
    /**
     * Find all groupelangues ordered by label.
     */
    /** @return array<int, \App\Entity\GroupeLangue> */
    public function findAllOrderedByLabel(): array
    {
        return $this->getEntityManager()->createQuery('SELECT l FROM App\Entity\GroupeLangue l ORDER BY l.label ASC')->getResult();
    }
}
