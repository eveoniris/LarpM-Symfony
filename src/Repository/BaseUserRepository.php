<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\BaseUser;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method BaseUser|null  find($id, $lockMode = null, $lockVersion = null)
 * @method BaseUser|null  findOneBy(array $criteria, array $orderBy = null)
 * @method list<BaseUser> findAll()
 * @method list<BaseUser> findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
/** @phpstan-ignore missingType.generics */
class BaseUserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BaseUser::class);
    }
}
