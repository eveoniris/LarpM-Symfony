<?php

namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
}
