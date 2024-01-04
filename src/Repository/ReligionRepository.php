<?php


namespace App\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * LarpManager\Repository\ReligionRepository.
 *
 * @author kevin
 */
class ReligionRepository extends BaseRepository
{
    /**
     * Find all religions ordered by label.
     *
     * @return ArrayCollection $religions
     */
    public function findAllOrderedByLabel()
    {
        return $this->getEntityManager()
            ->createQuery('SELECT r FROM App\Entity\Religion r ORDER BY r.label ASC')
            ->getResult();
    }

    /**
     * Find all public religions ordered by label.
     *
     * @return ArrayCollection $religions
     */
    public function findAllPublicOrderedByLabel()
    {
        return $this->getEntityManager()
            ->createQuery('SELECT r FROM App\Entity\Religion r WHERE r.secret = 0 ORDER BY r.label ASC')
            ->getResult();
    }
}
