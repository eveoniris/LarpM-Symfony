<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Participant;
use App\Entity\Personnage;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Un personnage a « déjà participé » s'il est rattaché à une participation à un GN dont la date de début est
 * passée. Une inscription à un GN futur ne compte pas : le personnage peut encore recevoir un bonus d'XP.
 */
final readonly class PersonnageParticipation
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function aDejaParticipe(Personnage $personnage, ?DateTimeInterface $maintenant = null): bool
    {
        $maintenant ??= new DateTimeImmutable();

        $nombre = (int) $this->entityManager
            ->createQueryBuilder()
            ->select('COUNT(p.id)')
            ->from(Participant::class, 'p')
            ->join('p.gn', 'g')
            ->where('p.personnage = :personnage')
            ->andWhere('g.date_debut IS NOT NULL')
            ->andWhere('g.date_debut <= :maintenant')
            ->setParameter('personnage', $personnage)
            ->setParameter('maintenant', $maintenant)
            ->getQuery()
            ->getSingleScalarResult();

        return $nombre > 0;
    }
}
