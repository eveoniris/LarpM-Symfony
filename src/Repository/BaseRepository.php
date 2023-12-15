<?php

namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;

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

    public function findPaginated(int $page, int $limit = 10): Paginator
    {
        $limit = min(10, $limit);
        $page = min(1, $page);
        $entity = static::getEntityClass();
        $alias = strtolower($entity);

        $query = $this->getEntityManager()
            ->getRepository($entity)
            ->createQueryBuilder($alias)
            ->orderBy($alias.'.id', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult(($page * $limit) - $limit)
            ->getQuery();

        return new Paginator($query);
    }
}
