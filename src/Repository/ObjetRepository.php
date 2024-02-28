<?php

namespace App\Repository;

use App\Entity\Rangement;
use App\Entity\Tag;
use Doctrine\ORM\QueryBuilder;
use JetBrains\PhpStorm\Deprecated;

class ObjetRepository extends BaseRepository
{
    final public const CRIT_WITHOUT = -1;

    /**
     * Trouve le nombre d'objets correspondant aux critères de recherche.
     */
    #[Deprecated]
    public function findCount(array $criteria): float|bool|int|string|null
    {
        $qb = $this->getQueryBuilder($criteria);
        $qb->select($qb->expr()->count('distinct o'));

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Trouve les objets correspondant aux critères de recherche.
     */
    #[Deprecated]
    public function findList(array $criteria, array $order = [], int $limit = 50, int $offset = 0)
    {
        $qb = $this->getQueryBuilder($criteria);
        $qb->setFirstResult($offset);
        $qb->setMaxResults($limit);
        $qb->orderBy('o.'.$order['by'], $order['dir']);

        return $qb->getQuery()->getResult();
    }

    #[Deprecated]
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

        $this->addTagCriteriaToQueryBuilder($criteria['tag'] ?? null, $qb);

        if ($criteria['numero'] ?? false) {
            $qb->andWhere('o.numero LIKE :numero');
            $qb->setParameter('numero', $criteria['numero']);
        }

        $this->addTagCriteriaToQueryBuilder($criteria['rangement'] ?? null, $qb);

        return $qb;
    }

    public function addTagCriteriaToQueryBuilder(null|string|int|Tag $criter, QueryBuilder $qb): QueryBuilder
    {
        $alias = $qb->getRootAliases()[0] ?? 'o';
        if (null === $criter) {
            return $qb;
        }

        if ($criter instanceof Tag) {
            if ($criter->getId() <= 0) {
                $criter = -1;
            } else {
                $criter = $criter->getNom();
            }
        }

        if (\is_numeric($criter)) {
            $criter = (int) $criter;

            if (-1 === $criter) {
                $qb->leftjoin($alias.'.tags', 't');
                $qb->andWhere('t.id is null');
            } else {
                $qb->join($alias.'.tags', 't');
                $qb->andWhere('t.id = :tag');
                $qb->setParameter('tag', $criter);
            }

            return $qb;
        }

        $qb->join($alias.'.tags', 't');
        $qb->andWhere('t.nom LIKE :tag');
        $qb->setParameter('tag', $criter);

        return $qb;
    }

    public function addRangementCriteriaToQueryBuilder(null|string|int|Rangement $criter, QueryBuilder $qb): QueryBuilder
    {
        $alias = $qb->getRootAliases()[0] ?? 'o';
        if (null === $criter) {
            return $qb;
        }

        if ($criter instanceof Rangement) {
            if ($criter->getId() <= 0) {
                $criter = -1;
            } else {
                $criter = $criter->getLabel();
            }
        }

        if (\is_numeric($criter)) {
            $criter = (int) $criter;
            if (-1 === $criter) {
                $qb->leftjoin($alias.'.rangement', 'r');
                $qb->andWhere('r.id is null');
            } else {
                $qb->andWhere($alias.'.rangement = :rangement');
                $qb->setParameter('rangement', $criter);
            }

            return $qb;
        }
        $qb->join($alias.'.rangement', 'r');
        $qb->andWhere('r.label LIKE :rangement');
        $qb->setParameter('rangement', $criter);

        return $qb;
    }
}
