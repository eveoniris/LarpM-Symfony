<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ApiClient;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ApiClient>
 */
class ApiClientRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApiClient::class);
    }

    public function findOneByUser(object $user): ?ApiClient
    {
        return $this->findOneBy(['user' => $user]);
    }

    public function findOneByClientId(string $clientId): ?ApiClient
    {
        return $this->findOneBy(['clientId' => $clientId]);
    }
}
