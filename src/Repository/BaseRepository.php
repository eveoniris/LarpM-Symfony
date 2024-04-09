<?php

namespace App\Repository;

use App\Entity\User;
use App\Service\OrderBy;
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

    public static function getEntityAlias(): string
    {
        return strtolower(str_replace(['App\Repository\\', 'Repository'], ['', ''], static::class));
    }

    public function findPaginated(int $page, int $limit = 10, string $orderby = 'id', string $orderdir = 'ASC', string $where = ''): Paginator
    {
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
                : (array) $iterable;

            if (0 === ++$i % $batchSize) {
                flush();
            }
        }
    }

    public function search(mixed $search, ?string $type, OrderBy $orderBy = null, string $alias = null): QueryBuilder
    {
        $orderBy ??= $this->orderBy;
        $alias ??= static::getEntityAlias();
        $query = $this->createQueryBuilder($alias);

        // Order only if allowed
        if (
            $orderBy->getOrderBy()
            && $this->isAllowedAttribute($orderBy->getOrderBy(), $this->sortAttributes($alias))
        ) {
            $query->orderBy($orderBy->getOrderBy(), $orderBy->getSort());
        } else {
            foreach ($this->sortAttributes($alias) as $sortDefinitions) {
                $attributeSort = $sortDefinitions[$orderBy->getSort()];
                $query->addOrderBy(key($attributeSort), current($attributeSort));
            }
        }

        if (empty($search) || self::SEARCH_NOONE === $type) {
            return $query;
        }

        if (empty($type) || self::SEARCH_ALL === $type) {
            $searchAttributes = $this->searchAttributes($alias);
            if (empty($searchAttributes)) {
                return $query;
            }

            foreach ($searchAttributes as $attribute) {
                $query->orWhere($attribute.' LIKE :value');
            }
            $query->setParameter('value', '%'.$search.'%');

            return $query;
        }

        // if type searched is not allowed we let a generic query
        if (!$this->isAllowedAttribute($type, $this->searchAttributes($alias))) {
            return $query;
        }

        return $query->where($query->expr()->like($alias.'.'.$type, "'%".$search."%'"));
    }

    public function searchAttributes(string $alias = null): array
    {
        $alias ??= $this->alias;

        return [
            $alias.'.id',
        ];
    }

    public function sortAttributes(string $alias = null): array
    {
        $alias ??= $this->alias;

        return [
            'id' => [OrderBy::ASC => [$alias.'.id' => OrderBy::ASC], OrderBy::DESC => [$alias.'.id' => OrderBy::DESC]],
        ];
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

    public function getAttributeName(string $aliased): string
    {
        return substr($aliased, strrpos($aliased, '.') + 1);
    }

    public function isAllowedAttribute(string $aliased, array $list): bool
    {
        $attribute = strtolower($this->getAttributeName($aliased));
        foreach ($list as $keyAttribute => $item) {
            if ($attribute === strtolower($keyAttribute)) {
                return true;
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
}
