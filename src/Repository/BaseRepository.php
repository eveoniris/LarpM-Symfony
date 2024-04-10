<?php

namespace App\Repository;

use App\Entity\User;
use App\Service\OrderBy;
use App\Service\PageRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<Entity>
 *
 * @implements PasswordUpgraderInterface<User>
 *
 * @method Entity|null find($id, $lockMode = null, $lockVersion = null)
 * @method Entity|null findOneBy(array $criteria, array $orderBy = null)
 * @method Entity[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
abstract class BaseRepository extends ServiceEntityRepository
{
    public const SEARCH_ALL = '*';
    public const SEARCH_NOONE = null;

    protected string $alias;

    public function __construct(
        ManagerRegistry $registry,
        protected OrderBy $orderBy,
        protected readonly RequestStack $requestStack
    ) {
        parent::__construct($registry, static::getEntityClass());
        $this->alias = static::getEntityAlias();
    }

    public static function getEntityClass(): string
    {
        return str_replace(['Repository', '\\\\'], ['', '\Entity\\'], static::class);
    }

    public static function getEntityAlias(): string
    {
        return strtolower(str_replace(['App\Repository\\', 'Repository'], ['', ''], static::class));
    }

    public function getColumnNames(): array
    {
        return $this->getEntityManager()
            ->getClassMetadata(static::getEntityClass())
            ->getColumnNames();
    }

    public function getFieldNames(): array
    {
        return $this->getEntityManager()
            ->getClassMetadata(static::getEntityClass())
            ->getFieldNames();
    }

    public function hasField(string $field): bool
    {
        return $this->getEntityManager()
            ->getClassMetadata(static::getEntityClass())
            ->hasField($field);
    }

    public function findPaginated(
        int $page,
        int $limit = 10,
        string $orderby = 'id',
        string $orderdir = 'ASC',
        string $where = ''
    ): Paginator {
        $limit = min(10, $limit);
        $page = max(1, $page);
        $alias = static::getEntityAlias();
        $orderdir = 'ASC' === $orderdir ? 'ASC' : 'DESC';

        $queryBase = $this->createQueryBuilder($alias)
            ->where($where)
            ->orderBy($alias.'.'.$orderby.' '.$orderdir.','.$alias.'.id', 'ASC')
            ->setMaxResults($limit)
            ->setFirstResult(($page * $limit) - $limit);

        if (!empty($where)) {
            $queryBase->where($where);
        }
        $query = $queryBase->getQuery();

        return new Paginator($query);
    }

    public function findPaginatedBy(array $criteria, array $orderBy = null, $limit = null, $offset = null): Paginator
    {
        $limit = min(10, $limit);
        $page = max(1, $offset);

        $query = $this->findBy($criteria)
            ->setMaxResults($limit)
            ->setFirstResult($page * $limit - $limit);

        if (!empty($orderBy)) {
            $query->orderBy($orderBy);
        }

        return new Paginator($query);
    }

    /**
     * Be sure to control orderBy allowed fields.
     */
    public function getPaginator(
        QueryBuilder $qb = null,
        int $limit = 25,
        int $page = 1,
        array $orderBy = [],
        string $alias = null,
        array $criterias = []
    ): Paginator {
        $alias ??= static::getEntityAlias();
        $limit = min(100, max(1, $limit));
        $offset = max(1, $page);
        $qb ??= $this->createQueryBuilder($alias);

        if (!empty($orderBy)) {
            foreach ($orderBy as $field => $direction) {
                $qb->addOrderBy($field, $direction);
            }
        }

        $qb->setFirstResult(($offset - 1) * $limit)
            ->setMaxResults($limit);

        if (!empty($criterias)) {
            foreach ($criterias as $criteria) {
                $qb->addCriteria($criteria);
            }
        }

        return new Paginator($qb);
    }

    public function findIterable(Query $query = null, int $batchSize = 100): \Generator
    {
        $query ??= $this->createQueryBuilder(static::getEntityAlias())->getQuery();
        $i = 0;
        /** @var Entity $iterable */
        foreach ($query->toIterable() as $iterable) {
            yield method_exists($iterable::class, 'getExportValue')
                ? $iterable->getExportValue()
                : (array)$iterable;

            if (0 === ++$i % $batchSize) {
                flush();
            }
        }
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function setAlias(string $alias): self
    {
        $this->alias = $alias;

        return $this;
    }

    public function searchPaginated(PageRequest $pageRequest): Paginator
    {
        $query = $this->search(
            $pageRequest->getSearchValue(),
            $pageRequest->getSearchType(),
            $pageRequest->getOrderBy(),
            $this->getAlias()
        )->getQuery();

        return $this->findPaginatedQuery(
            $query,
            $pageRequest->getLimit(),
            $pageRequest->getPage()
        );
    }

    public function findPaginatedQuery(
        Query $query,
        int $limit = null,
        $offset = null
    ): Paginator {
        $limit = min(100, max(1, $limit));
        $offset = max(1, $offset);

        $query->setMaxResults($limit)
            ->setFirstResult(($offset - 1) * $limit);

        return new Paginator($query);
    }

    /**
     * @param mixed $search a value to filter results who's "like" it
     * @param string|array|null $attributes Entity's attributes to search on
     * @param OrderBy|null $orderBy custom orderBy if not from the Request
     * @param string|null $alias the query alias for a specific one
     */
    public function search(
        mixed $search = null,
        null|string|array $attributes = self::SEARCH_NOONE,
        OrderBy $orderBy = null,
        string $alias = null
    ): QueryBuilder {
        $orderBy ??= $this->orderBy;
        $alias ??= static::getEntityAlias();
        $query = $this->createQueryBuilder($alias);

        // Order only if allowed and if exists
        if (
            $orderBy->getOrderBy()
            && $this->isAllowedAttribute($orderBy->getOrderBy(), $this->sortAttributes($alias))
        ) {
            $by = $orderBy->getOrderBy();
            if (!str_contains('.', $by)) {
                $by = $alias.'.'.$by;
            }
            $query->orderBy($by, $orderBy->getSort());
        } else {
            foreach ($this->sortAttributes($alias) as $sortDefinitions) {
                $attributeSort = $sortDefinitions[$orderBy->getSort()];
                $query->addOrderBy(key($attributeSort), current($attributeSort));
            }
        }

        // Any search to perform ?
        if (empty($search) || self::SEARCH_NOONE === $attributes) {
            return $query;
        }

        $searchAttributes = $this->searchAttributes($alias);
        if (empty($attributes) || self::SEARCH_ALL === $attributes) {
            if (empty($searchAttributes)) {
                return $query;
            }

            $attributes = $searchAttributes;
        }

        // if type searched is not allowed we let a generic query
        if (is_string($attributes)) {
            $attributes = [$attributes];
        }

        $asOne = false;
        foreach ($attributes as $attribute) {
            if (!$this->isAllowedAttribute($attribute, $searchAttributes)) {
                continue;
            }

            $asOne = true;

            $attributeAliased = $attribute;
            if (!str_contains('.', $attribute)) {
                $attributeAliased = $alias. '.' .$attribute;
            }

            $query->orWhere($attributeAliased.' LIKE :value');
        }

        return $asOne ? $query->setParameter('value', '%'.$search.'%') : $query;
    }

    public function isAllowedAttribute(string $aliased, array $list): bool
    {
        $attribute = strtolower($this->getAttributeName($aliased));

        if (self::SEARCH_ALL === $attribute) {
            return true;
        }

        foreach ($list as $keyAttribute => $item) {
            // From simple sort
            if ($attribute === strtolower($keyAttribute)) {
                return true;
            }

            // From simple search
            if (
                $attribute === strtolower($item)
                || $attribute === strtolower($this->getAttributeName($item))
            ) {
                return true;
            }

            // not from extended sort
            if (!is_array($item)) {
                continue;
            }

            if ($attribute === strtolower($this->getAttributeName(key($item[OrderBy::ASC])))) {
                return true;
            }

            if ($attribute === strtolower($this->getAttributeName(key($item[OrderBy::DESC])))) {
                return true;
            }
        }

        return false;
    }

    public function getAttributeName(string $aliased): string
    {
        // If we use a custom name by using query select alias
        $spliter = str_contains(strtoupper($aliased), ' AS ') ? ' AS ' : '.';

        if (!str_contains(strtoupper($aliased), $spliter)) {
            return $aliased;
        }

        return substr($aliased, strrpos($aliased, $spliter) + 1);
    }

    public function sortAttributes(string $alias = null): array
    {
        $alias ??= $this->alias;

        return [
            'id' => [OrderBy::ASC => [$alias.'.id' => OrderBy::ASC], OrderBy::DESC => [$alias.'.id' => OrderBy::DESC]],
        ];
    }

    public function searchAttributes(string $alias = null): array
    {
        $alias ??= $this->alias;

        return [
            $alias.'.id',
        ];
    }
}
