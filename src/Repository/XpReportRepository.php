<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Personnage;
use App\Entity\XpReport;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<XpReport>
 */
class XpReportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, XpReport::class);
    }

    /** Report par lequel ce mort a transmis son bonus, s'il existe. */
    public function findOneByMort(Personnage $mort): ?XpReport
    {
        return $this->findOneBy(['personnageMort' => $mort]);
    }

    /** Report par lequel ce personnage a reçu un bonus, s'il existe. */
    public function findOneByBeneficiaire(Personnage $beneficiaire): ?XpReport
    {
        return $this->findOneBy(['personnageBeneficiaire' => $beneficiaire]);
    }
}
