<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\DiscordRoleMapping;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<DiscordRoleMapping> */
class DiscordRoleMappingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DiscordRoleMapping::class);
    }
}
