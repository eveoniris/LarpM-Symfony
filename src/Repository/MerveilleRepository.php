<?php

declare(strict_types=1);

namespace App\Repository;

class MerveilleRepository extends BaseRepository
{
    /** @return array<int, string> */
    public function searchAttributes(?string $alias = null, bool $withAlias = true): array
    {
        $alias ??= static::getEntityAlias();

        return [
            self::SEARCH_ALL,
            $alias . '.nom',
            $alias . '.description',
        ];
    }

    /** @return array<int, \App\Entity\Merveille> */
    public function findAllActiveOrderedByLabel(): array
    {
        return $this
            ->getEntityManager()
            ->createQuery(<<<'DQL'
                SELECT m 
                FROM App\Entity\Merveille m 
                WHERE m.statut = 'active'
                ORDER BY m.nom ASC
                DQL)
            ->getResult();
    }
}
