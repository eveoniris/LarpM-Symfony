<?php


namespace App\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * LarpManager\Repository\AgeRepository.
 *
 * @author kevin
 */
class AgeRepository extends BaseRepository
{
    /**
     * Trouve tous les ages classé par index.
     *
     * @return ArrayCollection $ages
     */
    public function findAllOrderedByLabel()
    {
        return $this->getEntityManager()
            ->createQuery('SELECT a FROM App\Entity\Age a ORDER BY a.label ASC')
            ->getResult();
    }

    /**
     * Fourni tous les ages disponible à la création d'un personnage.
     */
    public function findAllOnCreation()
    {
        return $this->getEntityManager()
            ->createQuery('SELECT a FROM App\Entity\Age a WHERE a.enableCreation = true ORDER BY a.label ASC')
            ->getResult();
    }
}
