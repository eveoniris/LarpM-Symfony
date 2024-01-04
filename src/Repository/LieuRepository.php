<?php


namespace App\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * LarpManager\Repository\LieuRepository.
 *
 * @author kevin
 */
class LieuRepository extends BaseRepository
{
    /**
     * Trouve tous les lieux classé par ordre alphabétique.
     *
     * @return ArrayCollection $classes
     */
    public function findAllOrderedByNom()
    {
        return $this->getEntityManager()
            ->createQuery('SELECT l FROM App\Entity\Lieu l ORDER BY l.nom ASC')
            ->getResult();
    }
}
