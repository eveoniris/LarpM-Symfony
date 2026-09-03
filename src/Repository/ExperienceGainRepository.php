<?php

declare(strict_types=1);

namespace App\Repository;

class ExperienceGainRepository extends BaseRepository
{
    /** @return array<int, string> */
    public function searchAttributes(?string $alias = null, bool $withAlias = true): array
    {
        $alias ??= static::getEntityAlias();

        return [
            self::SEARCH_ALL,
            $alias . '.explanation',
        ];
    }
}
