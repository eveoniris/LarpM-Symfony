<?php


namespace App\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * LarpManager\Repository\LangueRepository.
 *
 * @author kevin
 */
class LangueRepository extends BaseRepository
{
    /**
     * Find all visible langues ordered by label.
     *
     * @return ArrayCollection $langues
     */
    public function findAllVisibleOrderedByLabel()
    {
        return $this->getEntityManager()
            ->createQuery('SELECT l FROM App\Entity\Langue l WHERE (l.secret = 0 or l.secret IS NULL) ORDER BY l.label ASC')
            ->getResult();
    }

    /**
     * Find all langues ordered by label.
     *
     * @return ArrayCollection $langues
     */
    public function findAllOrderedByLabel()
    {
        return $this->getEntityManager()
            ->createQuery('SELECT l FROM App\Entity\Langue l ORDER BY l.secret ASC, l.label ASC')
            ->getResult();
    }
}
