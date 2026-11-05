<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\DiscordGuild;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<DiscordGuild> */
class DiscordGuildRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DiscordGuild::class);
    }

    /** @return list<DiscordGuild> */
    public function findAllOrdered(): array
    {
        return array_values($this->findBy([], ['nom' => 'ASC']));
    }
}
