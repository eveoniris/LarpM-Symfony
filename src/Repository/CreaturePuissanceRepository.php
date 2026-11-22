<?php

declare(strict_types=1);

namespace App\Repository;

class CreaturePuissanceRepository extends BaseRepository
{
    /** @return array<int, string> */
    public function searchAttributes(?string $alias = null, bool $withAlias = true): array
    {
        $alias ??= static::getEntityAlias();

        return [
            self::SEARCH_ALL,
            $alias . '.nom',
        ];
    }

    /** @return array<int, \App\Entity\CreaturePuissance> */
    public function findEchelle(): array
    {
        return $this
            ->createQueryBuilder('c')
            ->orderBy('c.xp', 'ASC')
            ->addOrderBy('c.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
