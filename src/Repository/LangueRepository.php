<?php

declare(strict_types=1);

namespace App\Repository;

/**
 * LarpManager\Repository\LangueRepository.
 *
 * @author kevin
 */
class LangueRepository extends BaseRepository
{
    /**
     * Find all visible langues ordered by label.
     */
    /** @return array<int, \App\Entity\Langue> */
    public function findAllVisibleOrderedByLabel(): array
    {
        return $this->getEntityManager()->createQuery('SELECT l FROM App\Entity\Langue l WHERE (l.secret = 0 or l.secret IS NULL) ORDER BY l.label ASC')->getResult();
    }

    /**
     * Find all langues ordered by label.
     */
    /** @return array<int, \App\Entity\Langue> */
    public function findAllOrderedByLabel(): array
    {
        return $this->getEntityManager()->createQuery('SELECT l FROM App\Entity\Langue l ORDER BY l.secret ASC, l.label ASC')->getResult();
    }
}
