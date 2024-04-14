<?php

namespace App\Repository;

use App\Entity\Lignee;
use Doctrine\ORM\EntityRepository;

class LigneeRepository extends BaseRepository
{
    /**
     * Trouve toutes les lignées.
     */
    public function findAll(): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('l');
        $qb->from(Lignee::class, 'l');
        $qb->orderBy('l.id', 'ASC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Recherche d'une liste de lignees.
     *
     * @param unknown $type
     * @param unknown $value
     * @param unknown $limit
     * @param unknown $offset
     */
    public function findList($type, $value, $limit, $offset, array $order = [])
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('l');
        $qb->from(Lignee::class, 'l');
        if ($type && $value) {
            switch ($type) {
                case 'id':
                    $qb->andWhere('l.id = :value');
                    $qb->setParameter('value', $value);
                    break;
                case 'nom':
                    $qb->andWhere('l.nom LIKE :value');
                    $qb->setParameter('value', '%'.$value.'%');
                    break;
            }
        }

        $qb->setFirstResult($offset);
        $qb->setMaxResults($limit);
        $qb->orderBy('l.'.$order['by'], $order['dir']);

        return $qb->getQuery();
    }

    /**
     * Retourne le nombre de résultats correspondant à un critère.
     */
    public function findCount($type, ?string $value)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select($qb->expr()->count('l'));
        $qb->from(Lignee::class, 'l');

        if ($type && $value) {
            switch ($type) {
                case 'id':
                    $qb->andWhere('l.id = :value');
                    $qb->setParameter('value', $value);
                    break;
                case 'nom':
                    $qb->andWhere('l.nom LIKE :value');
                    $qb->setParameter('value', '%'.$value.'%');
                    break;
            }
        }

        return $qb->getQuery()->getSingleScalarResult();
    }
}
