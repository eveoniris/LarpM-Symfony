<?php


namespace App\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * LarpManager\Repository\ParticipantRepository.
 *
 * @author kevin
 */
class ParticipantRepository extends BaseRepository
{
    /**
     * Trouve le nombre d'utilisateurs correspondant aux critères de recherche.
     */
    public function findCount(array $criteria = [])
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select($qb->expr()->count('p'));
        $qb->from(\App\Entity\Participant::class, 'p');
        $qb->join('p.gn', 'gn');

        foreach ($criteria as $criter) {
            $qb->andWhere('?1');
            $qb->setParameter(1, $criter);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }
}
