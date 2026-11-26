<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Construction;
use App\Entity\Territoire;
use App\Service\OrderBy;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;

class TerritoireRepository extends BaseRepository
{
    /**
     * Find all territoire ordered by nom.
     */
    /**
     * @return array<int, Territoire>
     */
    public function findAllOrderedByNom(): array
    {
        return $this->getEntityManager()->createQuery('SELECT t FROM App\Entity\Territoire t ORDER BY t.nom ASC')->getResult();
    }

    /**
     * Fourni la liste des territoires n'étant pas dépendant d'un autre territoire.
     *
     * @return array<int, Territoire>
     */
    public function findRoot()
    {
        $query = $this->getEntityManager()->createQuery('SELECT t FROM App\Entity\Territoire t WHERE t.territoire IS NULL ORDER BY t.nom ASC');

        return $query->getResult();
    }

    /**
     * Fourni la liste des territoires étant dépendant d'un autre territoire et possédant des territoires.
     *
     * @return array<int, Territoire>
     */
    public function findRegions(): array
    {
        return $this->getEntityManager()->createQuery('SELECT t FROM App\Entity\Territoire t
             WHERE t.territoire IS NOT NULL
             AND EXISTS (SELECT 1 FROM App\Entity\Territoire child WHERE child.territoire = t)
             ORDER BY t.nom ASC')->getResult();
    }

    /**
     * Fourni la liste des territoires étant dépendant d'un autre territoire et ne possédant pas de territoires.
     *
     * @return array<int, Territoire>
     */
    public function findFiefs(): array
    {
        return $this->getEntityManager()->createQuery('SELECT t FROM App\Entity\Territoire t
             WHERE t.territoire IS NOT NULL
             AND NOT EXISTS (SELECT 1 FROM App\Entity\Territoire child WHERE child.territoire = t)
             ORDER BY t.nom ASC')->getResult();
    }

    /**
     * Trouve les fiefs correspondant aux critères de recherche.
     *
     * @param array<string, mixed>  $criteria
     * @param array<string, string> $order
     */
    public function findFiefsList(int $limit, int $offset, array $criteria = [], array $order = []): Query
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('distinct t')->addSelect('tgr')->addSelect('tpr')->addSelect('tp');
        $qb->from(Territoire::class, 't');

        $qb->leftJoin('t.groupe', 'tgr');
        $qb->leftjoin('t.territoire', 'tpr');
        $qb->leftjoin('tpr.territoire', 'tp');

        $count = 0;

        if (isset($criteria['t.id'], $criteria['t.nom'])) {
            $qb
                ->andWhere('LOWER(t.nom) LIKE ?1 OR t.id LIKE ?2')
                ->setParameter(1, '%' . preg_replace('/[\'"<>=*;]/', '', strtolower((string) $criteria['t.nom'])) . '%')
                ->setParameter(2, '%' . $criteria['t.id'] . '%');

            unset($criteria['t.id'], $criteria['t.nom']);
            $count = 2;
        }

        foreach ($criteria as $key => $value) {
            if ('t.nom' === $key) {
                $qb->andWhere(\sprintf('LOWER(%s) LIKE ?', $key) . $count)->setParameter($count, '%' . preg_replace('/[\'"<>=*;]/', '', strtolower((string) $value)) . '%');
            } else {
                $qb->andWhere($key . ' = ?' . $count)->setParameter($count, $value);
            }

            ++$count;
        }

        $qb->setFirstResult($offset);
        $qb->setMaxResults($limit);

        $defaultEntityAlias = str_contains((string) $order['by'], '.') ? '' : 't.';
        $qb->orderBy($defaultEntityAlias . $order['by'], $order['dir']);

        return $qb->getQuery();
    }

    /**
     * @return array<int, Territoire>
     */
    public function findProvinces(): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('distinct tpr');
        $qb->from(Territoire::class, 'tpr');
        $qb->leftJoin(Territoire::class, 't', 'WITH', 'tpr.id = t.territoire');
        $qb->join('tpr.territoire', 'tp');
        $qb->andWhere('tpr.territoire IS NOT NULL');
        $qb->andWhere('tp.territoire IS NULL');
        $qb->orderBy('tpr.nom', 'ASC');

        return $qb->getQuery()->getResult();
    }

    /** @param string|array<int|string, string|array<string, mixed>|null>|null $attributes */
    public function search(
        mixed $search = null,
        string|array|null $attributes = self::SEARCH_NOONE,
        ?OrderBy $orderBy = null,
        ?string $alias = null,
        ?QueryBuilder $query = null,
    ): QueryBuilder {
        $alias ??= static::getEntityAlias();

        $query ??= $this->createQueryBuilder($alias);
        $query->leftJoin($alias . '.appelation', 'appelation');

        return parent::search($search, $attributes, $orderBy, $alias, $query);
    }

    public function construction(QueryBuilder $queryBuilder, Construction $construction): QueryBuilder
    {
        $queryBuilder->innerJoin('territoire.constructions', 'c')->andWhere('c.id = :cid')->setParameter('cid', $construction->getId());

        return $queryBuilder;
    }

    /**
     * Retourne tous les territoires ayant un GeoJSON défini, sauf celui passé en paramètre.
     *
     * @return array<int, Territoire>
     */
    public function findWithGeoJson(int $excludeId): array
    {
        return $this->getEntityManager()->createQuery('SELECT t FROM App\Entity\Territoire t
                 WHERE t.geojson IS NOT NULL
                 AND t.id != :excludeId
                 ORDER BY t.nom ASC')->setParameter('excludeId', $excludeId)->getResult();
    }

    /**
     * @return array<int|string, string|array<string, mixed>|null>
     */
    public function searchAttributes(?string $alias = null, bool $withAlias = true): array
    {
        $alias ??= static::getEntityAlias();

        return [
            ...parent::searchAttributes($alias, false),
            $alias . '.nom', // => 'Libellé',
            $alias . '.description', // => 'Description',
            'appelation.label as appelation',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function sortAttributes(?string $alias = null): array
    {
        $alias ??= static::getEntityAlias();

        return [
            ...parent::sortAttributes($alias),
            $alias . '.nom' => [
                OrderBy::ASC => [$alias . '.nom' => OrderBy::ASC],
                OrderBy::DESC => [$alias . '.nom' => OrderBy::DESC],
            ],
            $alias . '.description' => [
                OrderBy::ASC => [$alias . '.description' => OrderBy::ASC],
                OrderBy::DESC => [$alias . '.description' => OrderBy::DESC],
            ],
            'appelation' => [
                OrderBy::ASC => ['appelation.label' => OrderBy::ASC],
                OrderBy::DESC => ['appelation.label' => OrderBy::DESC],
            ],
        ];
    }

    public function translateAttribute(string $attribute): string
    {
        $attribute = match ($this->getAttributeAsName($attribute)) {
            'appelation_id', 'appelation' => 'appelation',
            default => $attribute,
        };

        return parent::translateAttribute($attribute);
    }

    public function root(QueryBuilder $query, bool $root): QueryBuilder
    {
        if ($root) {
            $query->andWhere($this->alias . '.territoire = :value OR ' . $this->alias . '.territoire IS NULL');
        } else {
            $query->andWhere($this->alias . '.territoire = :value');
        }

        return $query->setParameter('value', $root);
    }

    /**
     * @return array<string, string>
     */
    public function translateAttributes(): array
    {
        return [
            ...parent::translateAttributes(),
            'appelation' => $this->translator->trans('Appelation', domain: 'repository'),
            'description' => $this->translator->trans('Description', domain: 'repository'),
            'nom' => $this->translator->trans('Nom', domain: 'repository'),
        ];
    }
}
