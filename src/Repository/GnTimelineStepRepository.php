<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Gn;
use App\Entity\GnTimelineStep;
use App\Enum\GnTimelineStatut;
use App\Enum\GnTimelineType;
use DateTimeImmutable;
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

    /**
     * Étapes d'action automatiques, à faire et échues.
     *
     * @return list<GnTimelineStep>
     */
    public function findAutoAFaireAvant(DateTimeImmutable $now): array
    {
        return $this
            ->createQueryBuilder('e')
            ->where('e.auto = true')
            ->andWhere('e.statut = :statut')
            ->andWhere('e.type != :info')
            ->andWhere('e.datePrevue <= :now')
            ->setParameter('statut', GnTimelineStatut::A_FAIRE)
            ->setParameter('info', GnTimelineType::INFO)
            ->setParameter('now', $now)
            ->orderBy('e.datePrevue', 'ASC')
            ->addOrderBy('e.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
