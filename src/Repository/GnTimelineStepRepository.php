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
        $etapes = $this->findBy(['gn' => $gn], ['id' => 'ASC']);
        // tri sur la date effective (un verrouillage suit la date du GN) ; sans date, en fin de liste
        usort(
            $etapes,
            static fn (GnTimelineStep $a, GnTimelineStep $b): int => (
                ($a->getDateEffective() ?? new DateTimeImmutable('9999-12-31')) <=> ($b->getDateEffective() ?? new DateTimeImmutable('9999-12-31'))
            ),
        );

        return $etapes;
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
            ->innerJoin('e.gn', 'g')
            ->where('e.auto = true')
            ->andWhere('e.statut = :statut')
            ->andWhere('e.type != :info')
            // un verrouillage suit la date de verrouillage du GN ; sans date, il reste manuel
            ->andWhere('(e.type = :verrouillage AND g.date_verrouillage IS NOT NULL AND g.date_verrouillage <= :now) OR (e.type != :verrouillage AND e.datePrevue <= :now)')
            ->setParameter('statut', GnTimelineStatut::A_FAIRE)
            ->setParameter('info', GnTimelineType::INFO)
            ->setParameter('verrouillage', GnTimelineType::VERROUILLAGE)
            ->setParameter('now', $now)
            ->orderBy('e.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
