<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\InterJeu;

/**
 * @extends BaseRepository<InterJeu>
 */
class InterJeuRepository extends BaseRepository
{
    /** @return array<int|string, string|array<string, mixed>|null> */
    public function searchAttributes(?string $alias = null, bool $withAlias = true): array
    {
        $alias ??= static::getEntityAlias();

        return [
            self::SEARCH_ALL,
            $alias . '.nom',
            $alias . '.anneeJeu',
        ];
    }

    /** @return array<string, string> */
    public function translateAttributes(): array
    {
        $alias = static::getEntityAlias();

        return [
            ...parent::translateAttributes(),
            $alias . '.nom' => 'Nom',
            $alias . '.anneeJeu' => 'Année en jeu',
        ];
    }
}
