<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;
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
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, static::getEntityClass());
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
}
