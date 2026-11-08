<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ApiClientRequest;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ApiClientRequest>
 */
class ApiClientRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApiClientRequest::class);
    }

    public function save(ApiClientRequest $demande): void
    {
        $this->getEntityManager()->persist($demande);
        $this->getEntityManager()->flush();
    }

    public function countPendingByRequester(User $user): int
    {
        return $this->count(['requester' => $user, 'status' => ApiClientRequest::EN_ATTENTE]);
    }

    /** @return list<ApiClientRequest> */
    public function findPending(): array
    {
        return $this->findBy(['status' => ApiClientRequest::EN_ATTENTE], ['createdAt' => 'ASC']);
    }

    /** @return list<ApiClientRequest> */
    public function findByRequester(User $user): array
    {
        return $this->findBy(['requester' => $user], ['createdAt' => 'DESC']);
    }
}
