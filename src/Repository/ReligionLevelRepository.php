<?php


namespace App\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * LarpManager\Repository\ReligionLevelRepository.
 *
 * @author kevin
 */
class ReligionLevelRepository extends BaseRepository
{
    /**
     * trouve tous les niveaux de religion classé par index.
     *
     * @return ArrayCollection $religionLevels
     */
    public function findAllOrderedByIndex()
    {
        return $this->getEntityManager()
            ->createQuery('SELECT rl FROM App\Entity\ReligionLevel rl ORDER BY rl.index ASC')
            ->getResult();
    }
}
