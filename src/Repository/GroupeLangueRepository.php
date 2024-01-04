<?php


namespace App\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * LarpManager\Repository\GroupeLangueRepository.
 *
 * @author kevin
 */
class GroupeLangueRepository extends BaseRepository
{
    /**
     * Find all groupelangues ordered by label.
     *
     * @return ArrayCollection $groupelangues
     */
    public function findAllOrderedByLabel()
    {
        return $this->getEntityManager()
            ->createQuery('SELECT l FROM App\Entity\GroupeLangue l ORDER BY l.label ASC')
            ->getResult();
    }
}
