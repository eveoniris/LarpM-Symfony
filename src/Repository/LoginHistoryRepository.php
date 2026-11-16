<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\LoginHistory;
use App\Entity\User;
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
