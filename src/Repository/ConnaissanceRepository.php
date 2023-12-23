<?php

namespace App\Repository;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityRepository;

/**
 * LarpManager\Repository\ConnaissanceRepository.
 *
 * @author Kevin F.
 */
class ConnaissanceRepository extends BaseRepository
{
    /**
     * @return ArrayCollection $Connaissance
     */
    public function findByNiveau($niveau)
    {
        return $this->getEntityManager()
            ->createQuery('SELECT c FROM App\Entity\Connaissance c Where c.niveau = ?1 AND (c.secret = 0 OR c.secret IS NULL) ORDER BY c.label ASC')
            ->setParameter(1, $niveau)
            ->getResult();
    }

    /**
     * @return ArrayCollection $religions
     */
    public function findAllOrderedByLabel()
    {
        return $this->getEntityManager()
            ->createQuery('SELECT c FROM App\Entity\Connaissance c ORDER BY c.label ASC')
            ->getResult();
    }

    /**
     * @return ArrayCollection $religions
     */
    public function findAllPublicOrderedByLabel()
    {
        return $this->getEntityManager()
            ->createQuery('SELECT c FROM App\Entity\Connaissance c WHERE c.secret = 0 ORDER BY c.label ASC')
            ->getResult();
    }
}
