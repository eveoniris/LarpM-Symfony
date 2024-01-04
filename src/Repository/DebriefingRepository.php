<?php


namespace App\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * LarpManager\Repository\DebriefingRepository.
 *
 * @author kevin
 */
class DebriefingRepository extends BaseRepository
{
    /**
     * Trouve les debriefing correspondant aux critères de recherche.
     */
    public function findCount(array $criteria = [])
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select($qb->expr()->count('d'));
        $qb->from(\App\Entity\Debriefing::class, 'd');

        foreach ($criteria as $criter) {
            $qb->andWhere('?1');
            $qb->setParameter(1, $criter);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }
}
