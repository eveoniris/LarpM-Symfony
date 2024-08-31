<?php

namespace App\Repository;

use App\Entity\Rumeur;

class RumeurRepository extends BaseRepository
{
    /**
     * Recherche d'une liste d'rumeur.
     *
     * @param unknown $type
     * @param unknown $value
     * @param unknown $limit
     * @param unknown $offset
     */
    public function findList($type, $value, array $order = [], $limit, $offset)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('i');
        $qb->from(Rumeur::class, 'i');
        if ($type && $value && 'text' === $type) {
            $qb->andWhere('i.text LIKE :value');
            $qb->setParameter('value', '%'.$value.'%');
        }
        if ($type && $value && 'territoire' === $type) {
            $qb->join('i.territoire', 't');
            $qb->andWhere('t.nom LIKE :value');
            $qb->setParameter('value', '%'.$value.'%');
        }

        $qb->setFirstResult($offset);
        $qb->setMaxResults($limit);
        $qb->orderBy('i.'.$order['by'], $order['dir']);

        return $qb->getQuery();
    }

    /**
     * Trouve le nombre d'rumeur correspondant aux critères de recherche.
     */
    public function findCount($type, ?string $value)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select($qb->expr()->count('r'));
        $qb->from(Rumeur::class, 'r');

        if ($type && $value && 'text' === $type) {
            $qb->andWhere('r.text LIKE :value');
            $qb->setParameter('value', '%'.$value.'%');
        }

        return $qb->getQuery()->getSingleScalarResult();
    }
}
