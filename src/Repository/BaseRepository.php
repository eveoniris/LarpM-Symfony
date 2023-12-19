<?php

namespace App\Repository;

use App\Entity\Billet;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

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

    public static function getEntityAlias(): string
    {
        return strtolower(str_replace(['App\Repository\\', 'Repository'], ['', ''], static::class));
    }

    public function findPaginated(int $page, int $limit = 10, string $orderby = 'id', string $orderdir = 'ASC'): Paginator
    {
        $limit = min(10, $limit);
        $page = max(1, $page);
        $entity = $this->getEntityName();
        $alias = static::getEntityAlias();


        $query = $this->getEntityManager()
            ->getRepository($entity)
            ->createQueryBuilder($alias)
            ->orderBy($alias.'.'.$orderby.' '.$orderdir.','.$alias.'.id', 'ASC')
            //->orderBy($alias.'.id', 'ASC')
            ->setMaxResults($limit)
            ->setFirstResult(($page * $limit) - $limit)
            ->getQuery();

        return new Paginator($query);
    }
}
