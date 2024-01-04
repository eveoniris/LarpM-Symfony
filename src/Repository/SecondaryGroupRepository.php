<?php


namespace App\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * LarpManager\Repository\SecondaryGroupRepository.
 *
 * @author kevin
 */
class SecondaryGroupRepository extends BaseRepository
{
    /**
     * Trouve tous les groupes secondaire publics.
     */
    public function findAllPublic()
    {
        return $this->getEntityManager()
            ->createQuery('SELECT g FROM App\Entity\SecondaryGroup g WHERE g.secret = false or g.secret is null')
            ->getResult();
    }

    /**
     * Trouve les groupes secondaires correspondants aux critères de recherche.
     *
     * @param unknown $limit
     * @param unknown $offset
     */
    public function findList($limit, $offset, array $criteria = [], array $order = [])
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('distinct g');
        $qb->from(\App\Entity\SecondaryGroup::class, 'g');

        foreach ($criteria as $critere) {
            $qb->andWhere('?1');
            $qb->setParameter(1, $critere);
        }

        $qb->setFirstResult($offset);
        $qb->setMaxResults($limit);
        $qb->orderBy('g.'.$order['by'], $order['dir']);

        return $qb->getQuery()->getResult();
    }

    /**
     * Compte les groupes secondaires correspondants aux critères de recherche.
     */
    public function findCount(array $criteria = [])
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select($qb->expr()->count('g'));
        $qb->from(\App\Entity\SecondaryGroup::class, 'g');

        foreach ($criteria as $critere) {
            $qb->andWhere('?1');
            $qb->setParameter(1, $critere);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }
}
