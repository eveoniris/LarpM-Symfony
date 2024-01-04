<?php


namespace App\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * LarpManager\Repository\BackgroundRepository.
 *
 * @author kevin
 */
class BackgroundRepository extends BaseRepository
{
    /**
     * Trouve les background correspondant aux critères de recherche.
     */
    public function findCount(array $criteria = [])
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select($qb->expr()->count('b'));
        $qb->from(\App\Entity\Background::class, 'b');

        foreach ($criteria as $criter) {
            $qb->andWhere('?1');
            $qb->setParameter(1, $criter);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function findBackgrounds($gnId)
    {
        return $this->getEntityManager()
            ->createQuery("SELECT b FROM App\Entity\Background b JOIN b.gn gn JOIN b.groupe g WHERE gn.id = ?1 ORDER BY g.numero ASC")
            ->setParameter(1, $gnId)
            ->getResult();
    }
}
