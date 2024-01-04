<?php


namespace App\Repository;

use App\Entity\Intrigue;
use Doctrine\ORM\EntityRepository;

/**
 * LarpManager\Repository\IntrigueRepository.
 *
 * @author kevin
 */
class IntrigueRepository extends BaseRepository
{
    /**
     * Recherche d'une liste d'intrigue.
     *
     * @param unknown $type
     * @param unknown $value
     * @param unknown $limit
     * @param unknown $offset
     */
    public function findList($type, $value, $limit, $offset, array $order = [])
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('i');
        $qb->from(\App\Entity\Intrigue::class, 'i');
        if ($type && $value && 'titre' === $type) {
            $qb->andWhere('i.titre LIKE :value');
            $qb->setParameter('value', '%'.$value.'%');
        }

        $qb->setFirstResult($offset);
        $qb->setMaxResults($limit);
        $qb->orderBy('i.'.$order['by'], $order['dir']);

        return $qb->getQuery()->getResult();
    }

    /**
     * Trouve le nombre d'intrigue correspondant aux critères de recherche.
     */
    public function findCount($type, ?string $value)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select($qb->expr()->count('i'));
        $qb->from(\App\Entity\Intrigue::class, 'i');

        if ($type && $value && 'titre' === $type) {
            $qb->andWhere('i.titre LIKE :value');
            $qb->setParameter('value', '%'.$value.'%');
        }

        return $qb->getQuery()->getSingleScalarResult();
    }
}
