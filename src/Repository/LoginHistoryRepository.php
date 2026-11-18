<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\LoginHistory;
use App\Entity\User;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LoginHistory>
 */
class LoginHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LoginHistory::class);
    }

    /** @return list<LoginHistory> */
    public function findLatestByUser(User $user, int $limit = 20): array
    {
        return $this->findBy(['user' => $user], ['date' => 'DESC', 'id' => 'DESC'], $limit);
    }

    /** @return list<string> adresses IP distinctes, non encore anonymisées, des lignes antérieures à la date */
    public function findIpsToAnonymize(DateTimeImmutable $before): array
    {
        /** @var list<array{ip: string}> $rows */
        $rows = $this
            ->createQueryBuilder('l')
            ->select('DISTINCT l.ip AS ip')
            ->where('l.date < :before')
            ->andWhere('l.ipAnonymized = false')
            ->andWhere('l.ip IS NOT NULL')
            ->setParameter('before', $before)
            ->getQuery()
            ->getArrayResult();

        return array_column($rows, 'ip');
    }

    public function countToAnonymize(DateTimeImmutable $before): int
    {
        return (int) $this
            ->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->where('l.date < :before')
            ->andWhere('l.ipAnonymized = false')
            ->andWhere('l.ip IS NOT NULL')
            ->setParameter('before', $before)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** Remplace une IP par sa version anonymisée (null si invalide) sur les lignes antérieures à la date. */
    public function anonymizeIp(string $ip, ?string $anonymized, DateTimeImmutable $before): int
    {
        return (int) $this
            ->createQueryBuilder('l')
            ->update()
            ->set('l.ip', ':anonymized')
            ->set('l.ipAnonymized', 'true')
            ->where('l.ip = :ip')
            ->andWhere('l.date < :before')
            ->andWhere('l.ipAnonymized = false')
            ->setParameter('anonymized', $anonymized)
            ->setParameter('ip', $ip)
            ->setParameter('before', $before)
            ->getQuery()
            ->execute();
    }

    public function countOlderThan(DateTimeImmutable $before): int
    {
        return (int) $this
            ->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->where('l.date < :before')
            ->setParameter('before', $before)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function deleteOlderThan(DateTimeImmutable $before): int
    {
        return (int) $this
            ->createQueryBuilder('l')
            ->delete()
            ->where('l.date < :before')
            ->setParameter('before', $before)
            ->getQuery()
            ->execute();
    }

    /**
     * Id de la première connexion réussie pour chaque empreinte d'un utilisateur.
     *
     * Sert à repérer un nouvel appareil : une connexion est « nouvelle » quand son id est le premier
     * de son empreinte, sauf pour la toute première connexion du compte.
     *
     * @return array<string, int> empreinte => id
     */
    public function firstSuccessIdByFingerprint(User $user): array
    {
        /** @var list<array{fingerprint: string, firstId: int|string}> $rows */
        $rows = $this
            ->createQueryBuilder('l')
            ->select('l.fingerprint AS fingerprint', 'MIN(l.id) AS firstId')
            ->where('l.user = :user')
            ->andWhere('l.success = true')
            ->andWhere('l.fingerprint IS NOT NULL')
            ->groupBy('l.fingerprint')
            ->setParameter('user', $user)
            ->getQuery()
            ->getArrayResult();

        $result = [];
        foreach ($rows as $row) {
            $result[$row['fingerprint']] = (int) $row['firstId'];
        }

        return $result;
    }
}
