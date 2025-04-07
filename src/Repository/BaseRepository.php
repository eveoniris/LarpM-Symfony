<?php

namespace App\Repository;

use App\Entity\User;
use App\Service\OrderBy;
use App\Service\PagerService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Proxy\Proxy;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
use JetBrains\PhpStorm\Deprecated;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

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

    public const ITERATE_EXPORT = 'export';
    public const ITERATE_EXPORT_HEADER = 'export_header';
    public const ITERATE_OBJECT = 'object';
    public const ITERATE_ARRAY = 'array';

    protected string $alias;

    public function __construct(
        ManagerRegistry $registry,
        protected OrderBy $orderBy,
        protected readonly RequestStack $requestStack,
        protected readonly TranslatorInterface $translator,
        protected readonly EntityManagerInterface $entityManager,
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

    public function findAll(): array
    {
        return $this->findBy([], ['label' => 'ASC']);
    }

    #[Deprecated]
    public function findPaginated(
        int $page,
        int $limit = 10,
        string $orderby = 'id',
        string $orderdir = 'ASC',
        string $where = '',
    ): Paginator {
        $limit = min(10, $limit);
        $page = max(1, $page);
        $alias = static::getEntityAlias();
        $orderdir = 'ASC' === $orderdir ? 'ASC' : 'DESC';

        $queryBase = $this->createQueryBuilder($alias)
            // ->where($where)
            ->orderBy($alias.'.'.$orderby.' '.$orderdir.','.$alias.'.id', 'ASC')
            ->setMaxResults($limit)
            ->setFirstResult(($page * $limit) - $limit);

        if (!empty($where) && '' != $where) {
            $queryBase->where($where);
        }
        $query = $queryBase->getQuery();

        return new Paginator($query);
    }

    #[Deprecated]
    public function findPaginatedBy(array $criteria, ?array $orderBy = null, $limit = null, $page = null): Paginator
    {
        $limit = min(10, $limit);
        $page = max(1, $page);
        $alias = static::getEntityAlias();
        $orderdir = $this->orderBy->getSort();

        /*
        $query = $this->createQueryBuilder($alias)
            ->where($where)
            ->orderBy($alias.'.'.$orderby.' '.$orderdir.','.$alias.'.id', 'ASC')
            ->setMaxResults($limit)
            ->setFirstResult(($page * $limit) - $limit);
        */

        $query = $this->findBy($criteria)
            ->setMaxResults($limit)
            ->setFirstResult($page * $limit - $limit);

        if (!empty($orderBy)) {
            $query->orderBy($orderBy);
        }

        return new Paginator($query);
    }

    public function getPaginator(
        ?QueryBuilder $qb = null,
        int $limit = 25,
        int $page = 1,
        array $orderBy = [],
        ?string $alias = null,
        array $criterias = [],
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

    public function findIterable(
        Query|QueryBuilder|null $query = null,
        int $batchSize = 100,
        ?string $alias = null,
        string $iterableMode = self::ITERATE_EXPORT_HEADER,
    ): \Generator {
        $alias ??= static::getEntityAlias();
        $query ??= $this->createQueryBuilder($alias);
        $this->addOrderBy($query);
        if ($query instanceof QueryBuilder) {
            $query = $query->getQuery();
        }

        $i = 0;
        $hydratationMode = static::ITERATE_ARRAY === $iterableMode ? AbstractQuery::HYDRATE_ARRAY : AbstractQuery::HYDRATE_OBJECT;
        /** @var Entity $iterable */
        foreach ($query->toIterable(hydrationMode: $hydratationMode) as $iterable) {
            if (
                \in_array($iterableMode, [static::ITERATE_EXPORT, static::ITERATE_EXPORT_HEADER], true)
                && method_exists($iterable, 'getExportValue')
            ) {
                if (static::ITERATE_EXPORT_HEADER === $iterableMode) {
                    yield array_keys($iterable->getExportValue());
                }
                yield $iterable->getExportValue();
                continue;
            }

            yield $iterable;

            if (0 === ++$i % $batchSize) {
                flush();
                $this->entityManager->flush();
                $this->entityManager->clear();
            }
        }

        flush();
        $this->entityManager->flush();
        $this->entityManager->clear();
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

    public function searchPaginated(PagerService $pageRequest, ?QueryBuilder $queryBuilder = null): Paginator
    {
        $query = $this->search(
            $pageRequest->getSearchValue(),
            $pageRequest->getSearchType(),
            $pageRequest->getOrderBy(),
            $this->getAlias(),
            $queryBuilder
        )->getQuery();
        //  $query->setHydrationMode(Query::HYDRATE_SCALAR);

        return $this->findPaginatedQuery(
            $query,
            $pageRequest->getLimit(),
            $pageRequest->getPage()
        );
    }

    /**
     * Check if class is an Entity.
     */
    public function isEntity(string|object $class): bool
    {
        if (is_object($class)) {
            $class = ($class instanceof Proxy)
                ? get_parent_class($class)
                : get_class($class);
        }

        return !$this->getEntityManager()->getMetadataFactory()->isTransient($class);
    }

    public function findPaginatedQuery(
        Query $query,
        ?int $limit = null,
        $offset = null,
    ): Paginator {
        $limit = min(100, max(1, $limit));
        $offset = max(1, $offset);

        $query->setMaxResults($limit)
            ->setFirstResult(($offset - 1) * $limit);

        return new Paginator($query, false);
    }

    /**
     * @param mixed             $search     a value to filter results who's "like" it
     * @param string|array|null $attributes Entity's attributes to search on
     * @param OrderBy|null      $orderBy    custom orderBy if not from the Request
     * @param string|null       $alias      the query alias for a specific one
     */
    public function search(
        mixed $search = null,
        string|array|null $attributes = self::SEARCH_NOONE,
        ?OrderBy $orderBy = null,
        ?string $alias = null,
        ?QueryBuilder $query = null,
    ): QueryBuilder {
        $orderBy ??= $this->orderBy;
        $alias ??= static::getEntityAlias();

        $query ??= $this->createQueryBuilder($alias);
        // Order only if allowed and if exists
        $this->addOrderBy($query, $orderBy, $alias);
        // Any search to perform ?
        if (empty($search) || self::SEARCH_NOONE === $attributes) {
            return $query;
        }

        $searchAttributes = $this->searchAttributes($alias);
        if (empty($attributes) || self::SEARCH_ALL === $attributes) {
            if (empty($searchAttributes)) {
                return $query;
            }

            foreach ([self::SEARCH_NOONE, self::SEARCH_ALL] as $keyToClean) {
                if (isset($searchAttributes[$keyToClean])) {
                    unset($searchAttributes[$keyToClean]);
                }

                $keyOfValue = array_search($keyToClean, $searchAttributes, true);
                if (false !== $keyOfValue) {
                    unset($searchAttributes[$keyOfValue]);
                }
            }

            $attributes = $searchAttributes;
        }

        // if type searched is not allowed we let a generic query
        if (is_string($attributes)) {
            $attributes = [$attributes];
        }

        $asOne = false;
        $orWhere = [];
        foreach ($attributes as $attribute => $label) {
            // if attribute is without label
            if (is_int($attribute)) {
                $attribute = $label;
            }

            if (!$this->isAllowedAttribute($attribute, $searchAttributes)) {
                continue;
            }

            $asOne = true;

            $attributeAliased = $attribute;
            if (!str_contains($attribute, '.')) {
                $attributeAliased = $alias.'.'.$attribute;
            }

            $attributeAliased = $this->getAttributeWhereName($attributeAliased);

            $orWhere[] = $attributeAliased.' LIKE :value';
        }
        if (!empty($orWhere)) {
            $query->andWhere(implode(' OR ', $orWhere));
        }

        return $asOne ? $query->setParameter('value', '%'.$search.'%') : $query;
    }

    public function addOrderBy(
        Query|QueryBuilder|null $query = null,
        ?OrderBy $orderBy = null,
        ?string $alias = null,
    ): Query|QueryBuilder {
        $orderBy ??= $this->orderBy;
        $alias ??= static::getEntityAlias();
        $query ??= $this->createQueryBuilder($alias);
        if (
            $orderBy->getOrderBy()
            && $this->isAllowedAttribute($orderBy->getOrderBy(), $this->sortAttributes($alias))
        ) {
            $by = $orderBy->getOrderBy();
            if (!str_contains($by, '.')) {
                $by = $alias.'.'.$by;
            }
            $query->orderBy($by, $orderBy->getSort());
        } else {
            $asAttributes = $this->searchAttributesAs($alias);
            foreach ($this->sortAttributes($alias) as $sortDefinitions) {
                $attributeSort = $sortDefinitions[$orderBy->getSort()];
                $query->addOrderBy(key($attributeSort), current($attributeSort));

                // handle aliased fields
                if (isset($asAttributes[key($attributeSort)])) {
                    $query->addSelect(
                        $asAttributes[key($attributeSort)].' AS '.key($attributeSort)
                    );
                }
            }
        }

        return $query;
    }

    public function isAs(string $attribute): bool
    {
        return str_contains(strtoupper($attribute), ' AS ');
    }

    public function isAllowedAttribute(string $aliased, array $list): bool
    {
        $attribute = strtolower($this->getAttributeName($aliased));

        if (self::SEARCH_ALL === $attribute) {
            return true;
        }

        foreach ($list as $keyAttribute => $item) {
            // From simple sort
            if (strtolower($attribute) === strtolower($keyAttribute)) {
                return true;
            }

            // From simple search
            if (
                is_string($item)
                && (strtolower($attribute) === strtolower($item)
                    || strtolower($attribute) === strtolower($this->getAttributeName($item))
                    || (str_contains(strtolower($item), strtolower($attribute)) && str_contains(strtolower($item), strtolower($this->getAttributeName($item))))
                )
            ) {
                return true;
            }
            // isAllowedAttribute(foreach) > keyAttribute:5/item:classe.nom as classe/attribute:nom/aliased:classe.nom/getAttributeName:nom as classe

            // from aliased
            if (strtolower($aliased) === strtolower($keyAttribute)) {
                return true;
            }

            // not from extended sort
            if (!is_array($item)) {
                continue;
            }

            if (strtolower($attribute) === strtolower($this->getAttributeName(key($item[OrderBy::ASC])))) {
                return true;
            }

            if (strtolower($attribute) === strtolower($this->getAttributeName(key($item[OrderBy::DESC])))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get an aliased attribute and return field name
     * exemple :
     * "table.attribute_name AS UserId" will return "attribute_name"
     * "table.attribute_name" will return "attribute_name"
     * "attribute_name As UserId" will return "attribute_name"
     * "attribute_name" will return "attribute_name".
     */
    public function getAttributeName(string $aliased): string
    {
        $upper = strtoupper($aliased);
        $asPos = strrpos($upper, ' AS ');
        $dotPos = strrpos($upper, '.') ?? 0;
        $len = strlen($aliased);
        $subOffset = $dotPos ? $dotPos + 1 : 0;
        $subLength = $asPos ? $len - $asPos + 5 : null;

        return trim(substr($aliased, $subOffset, $subLength));
    }

    /**
     * Get an attribute or an aliased attribute and return the alias
     * Exemple :
     * "table.attribute_name AS UserId" will return "UserId"
     * "table.attribute_name" will return "attribute_name"
     * "attribute_name As UserId" will return "UserId"
     * "attribute_name" will return "attribute_name".
     */
    public function getAttributeAsName(string $aliased): ?string
    {
        $upper = strtoupper($aliased);
        $asPos = strrpos($upper, ' AS ');
        if (false !== $asPos) {
            return trim(substr($aliased, $asPos + 4));
        }

        $dotPos = strrpos($upper, '.');
        if (false !== $dotPos) {
            return trim(substr($aliased, $dotPos + 1));
        }

        return trim($aliased);
    }

    /**
     * Get an attribute or an aliased attribute and return the attribute with path without alias
     * Exemple :
     * "table.attribute_name AS UserId" will return "table.attribute_name"
     * "table.attribute_name" will return "table.attribute_name"
     * "attribute_name As UserId" will return "attribute_name"
     * "attribute_name" will return "attribute_name".
     */
    public function getAttributeWhereName(string $aliased): string
    {
        $upper = strtoupper($aliased);
        $asPos = strrpos($upper, ' AS ');

        return trim(substr($aliased, 0, $asPos > 0 ? $asPos : null));
    }

    public function sortAttributes(?string $alias = null): array
    {
        $alias ??= $this->alias;

        return [
            'id' => [OrderBy::ASC => [$alias.'.id' => OrderBy::ASC], OrderBy::DESC => [$alias.'.id' => OrderBy::DESC]],
        ];
    }

    public function searchAttributes(): array
    {
        return [
            self::SEARCH_ALL,
        ];
    }

    /**
     * Return all alias of searchAttributes
     * Alias is the key, Atribute is the value.
     */
    public function searchAttributesAs(?string $alias = null): array
    {
        $asAttributes = [];

        foreach ($this->searchAttributes($alias) as $attribute) {
            if (!$this->isAs($attribute)) {
                continue;
            }

            $asAttributes[$this->getAttributeAsName($attribute)] = $this->getAttributeWhereName($attribute);
        }

        return $asAttributes;
    }

    public function translateAttributes(): array
    {
        return [
            self::SEARCH_ALL => $this->translator->trans('Tout critère', domain: 'repository'),
            self::SEARCH_NOONE => $this->translator->trans('Aucun', domain: 'repository'),
            'id' => $this->translator->trans('Id', domain: 'repository'),
        ];
    }

    public function translateAttribute(string $attribute): string
    {
        $baseName = $this->getAttributeName($attribute);
        $aliasName = $this->getAttributeAsName($attribute);

        if (isset($this->translateAttributes()[$aliasName])) {
            return $this->translateAttributes()[$aliasName];
        }

        if (
            !isset($this->translateAttributes()[$attribute])
            && isset($this->translateAttributes()[$baseName])
        ) {
            $attribute = $baseName;
        }

        return $this->translateAttributes()[$attribute] ?? $attribute;
    }
}
