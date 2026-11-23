<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\IpBloquee;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<IpBloquee>
 */
class IpBloqueeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, IpBloquee::class);
    }

    /** @return list<string> plages (CIDR) des blocages non expirés */
    public function findActiveCidrs(?DateTimeImmutable $now = null): array
    {
        /** @var list<array{cidr: string}> $rows */
        $rows = $this
            ->createQueryBuilder('b')
            ->select('b.cidr AS cidr')
            ->where('b.expiresAt > :now')
            ->setParameter('now', $now ?? new DateTimeImmutable())
            ->getQuery()
            ->getArrayResult();

        return array_column($rows, 'cidr');
    }

    /** Supprime les blocages expirés avant la date, renvoie le nombre de lignes supprimées. */
    public function deleteExpiredBefore(DateTimeImmutable $before): int
    {
        return (int) $this
            ->createQueryBuilder('b')
            ->delete()
            ->where('b.expiresAt < :before')
            ->setParameter('before', $before)
            ->getQuery()
            ->execute();
    }
}
