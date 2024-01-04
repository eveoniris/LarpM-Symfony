<?php


namespace App\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * LarpManager\Repository\ConstructionRepository.
 *
 * @author Kevin F.
 */
class ConstructionRepository extends BaseRepository
{
    /**
     * Find all constructions ordered by label.
     *
     * @return ArrayCollection $constructions
     */
    public function findAllOrderedByLabel(): array
    {
        return $this->getEntityManager()
            ->createQuery('SELECT r FROM App\Entity\Construction r ORDER BY r.label ASC')
            ->getResult();
    }

    /**
     * Find all constructions ordered by label.
     *
     * @return ArrayCollection $constructions
     */
    public function findAll(): array
    {
        return $this->getEntityManager()
            ->createQuery('SELECT r FROM App\Entity\Construction r ORDER BY r.label ASC')
            ->getResult();
    }
}
