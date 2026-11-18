<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\LoginHistory;
use App\Entity\User;
use App\Enum\LoginMethod;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LoginHistory>
 */
// @mago-expect lint:cyclomatic-complexity,too-many-methods
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

    /**
     * Vue globale (administrateurs) : tentatives de connexion filtrées, de la plus récente à la plus ancienne.
     *
     * @param array{q?: string, result?: string, method?: ?LoginMethod, reason?: string, ip?: string, from?: ?DateTimeImmutable, to?: ?DateTimeImmutable} $filters
     *        `to` est exclusif (lendemain minuit pour inclure un jour entier)
     *
     * @return Paginator<LoginHistory>
     */
    public function searchPaginated(array $filters, int $page, int $limit): Paginator
    {
        $limit = min(100, max(1, $limit));
        $queryBuilder = $this
            ->createQueryBuilder('l')
            ->leftJoin('l.user', 'u')
            ->addSelect('u')
            ->orderBy('l.date', 'DESC')
            ->addOrderBy('l.id', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult((max(1, $page) - 1) * $limit);

        $q = trim($filters['q'] ?? '');
        if ('' !== $q) {
            $queryBuilder
                ->andWhere('l.identifier LIKE :q OR l.ip LIKE :q OR l.userAgent LIKE :q')
                ->setParameter('q', '%' . addcslashes($q, characters: '%_\\') . '%');
        }

        match ($filters['result'] ?? '') {
            'success' => $queryBuilder->andWhere('l.success = true'),
            'failure' => $queryBuilder->andWhere('l.success = false'),
            default => null,
        };

        $this->applyEquals($queryBuilder, 'l.method', 'method', $filters['method'] ?? null);
        $this->applyEquals($queryBuilder, 'l.failureReason', 'reason', '' === ($filters['reason'] ?? '') ? null : $filters['reason'] ?? null);
        $this->applyEquals($queryBuilder, 'l.ip', 'ip', '' === ($filters['ip'] ?? '') ? null : $filters['ip'] ?? null);

        if (null !== ($filters['from'] ?? null)) {
            $queryBuilder->andWhere('l.date >= :from')->setParameter('from', $filters['from']);
        }

        if (null !== ($filters['to'] ?? null)) {
            $queryBuilder->andWhere('l.date < :to')->setParameter('to', $filters['to']);
        }

        return new Paginator($queryBuilder->getQuery());
    }

    /** @return list<string> raisons d'échec réellement présentes, pour le filtre */
    public function findDistinctFailureReasons(): array
    {
        $rows = $this
            ->createQueryBuilder('l')
            ->select('DISTINCT l.failureReason AS reason')
            ->where('l.failureReason IS NOT NULL')
            ->orderBy('l.failureReason')
            ->getQuery()
            ->getScalarResult();

        return array_column($rows, 'reason');
    }

    private function applyEquals(QueryBuilder $queryBuilder, string $field, string $parameter, mixed $value): void
    {
        if (null === $value) {
            return;
        }

        $queryBuilder->andWhere($field . ' = :' . $parameter)->setParameter($parameter, $value);
    }
}
