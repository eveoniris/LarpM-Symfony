<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\RulebookRevision;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RulebookRevision>
 */
class RulebookRevisionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RulebookRevision::class);
    }
}
