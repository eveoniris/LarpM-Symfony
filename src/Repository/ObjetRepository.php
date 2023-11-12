<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

class ObjetRepository extends EntityRepository
{
    final public const CRIT_WITHOUT = -1;

    /**
     * Trouve tous les objets.
     */
    public function findAll(): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('o');
        $qb->from(\App\Entity\Objet::class, 'o');
        $qb->orderBy('o.id', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Trouve le nombre d'objets correspondant aux critères de recherche.
     */
    public function findCount(array $criteria): float|bool|int|string|null
    {
        $qb = $this->getQueryBuilder($criteria);
        $qb->select($qb->expr()->count('distinct o'));

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Trouve les objets correspondant aux critères de recherche.
     */
    public function findList(array $criteria, array $order = [], int $limit = 50, int $offset = 0)
    {
        $qb = $this->getQueryBuilder($criteria);
        $qb->setFirstResult($offset);
        $qb->setMaxResults($limit);
        $qb->orderBy('o.'.$order['by'], $order['dir']);

        return $qb->getQuery()->getResult();
    }

    protected function getQueryBuilder(array $criteria): QueryBuilder
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('o');
        $qb->from(\App\Entity\Objet::class, 'o', 'o');
        $qb->join('o.photo', 'p');

        $allowed = ['nom', 'description', 'numero'];
        foreach ($allowed as $field) {
            if ($criteria[$field] ?? false) {
                $qb->andWhere('o.'.$field.' LIKE :field ');
                $qb->setParameter('field', '%'.$criteria[$field].'%');
            }
        }

        if ($criteria['tag'] ?? false) {
            $criter = $criteria['tag'];

            if (is_numeric($criter)) {
                $criter = (int) $criter;

                if (-1 === $criter) {
                    $qb->leftjoin('o.tags', 't');
                    $qb->andWhere('t.id is null');
                } else {
                    $qb->join('o.tags', 't');
                    $qb->andWhere('t.id = :tag');
                    $qb->setParameter('tag', $criter);
                }
            } else {
                $qb->join('o.tags', 't');
                $qb->andWhere('t.nom LIKE :tag');
                $qb->setParameter('tag', $criter);
            }
        }

        if ($criteria['numero'] ?? false) {
            $qb->andWhere('o.numero LIKE :numero');
            $qb->setParameter('numero', $criteria['numero']);
        }

        if (isset($criteria['rangement'])) {
            $criter = $criteria['rangement'];

            if (is_numeric($criter)) {
                $criter = (int) $criter;
                if (-1 === $criter) {
                    $qb->leftjoin('o.rangement', 'r');
                    $qb->andWhere('r.id is null');
                } else {
                    $qb->andWhere('o.rangement = :rangement');
                    $qb->setParameter('rangement', $criter);
                }
            } else {
                $qb->join('o.rangement', 'r');
                $qb->andWhere('r.label LIKE :rangement');
                $qb->setParameter('rangement', $criter);
            }
        }

        return $qb;
    }
}
