<?php


namespace App\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * LarpManager\Repository\CompetenceFamilyRepository.
 *
 * @author kevin
 */
class CompetenceFamilyRepository extends BaseRepository
{
    /**
     * Find all classes ordered by label.
     *
     * @return ArrayCollection $competenceFamilies
     */
    public function findAllOrderedByLabel()
    {
        return $this->getEntityManager()
            ->createQuery('SELECT cf FROM App\Entity\CompetenceFamily cf ORDER BY cf.label ASC')
            ->getResult();
    }
}
