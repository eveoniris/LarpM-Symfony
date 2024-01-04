<?php


namespace App\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * LarpManager\Repository\TechnologieRepository.
 *
 * @author Kevin F.
 */
class TechnologieRepository extends BaseRepository
{
    /**
     * Find all public technologies ordered by label.
     *
     * @return ArrayCollection $technologies
     */
    public function findPublicOrderedByLabel()
    {
        return $this->getEntityManager()
            ->createQuery('SELECT r FROM App\Entity\Technologie r WHERE r.secret = 0 ORDER BY r.label ASC')
            ->getResult();
    }

    /**
     * Find all technologies ordered by label.
     *
     * @return ArrayCollection $technologies
     */
    public function findAllOrderedByLabel()
    {
        return $this->getEntityManager()
            ->createQuery('SELECT r FROM App\Entity\Technologie r ORDER BY r.label ASC')
            ->getResult();
    }
}
