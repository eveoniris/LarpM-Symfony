<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Gn;
use App\Entity\GnTimelineStep;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GnTimelineStep>
 */
class GnTimelineStepRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GnTimelineStep::class);
    }

    /** @return list<GnTimelineStep> */
    public function findAllOrdered(): array
    {
        return $this->findBy([], ['datePrevue' => 'ASC', 'id' => 'ASC']);
    }

    /** @return list<GnTimelineStep> */
    public function findByGn(Gn $gn): array
    {
        return $this->findBy(['gn' => $gn], ['datePrevue' => 'ASC', 'id' => 'ASC']);
    }
}
