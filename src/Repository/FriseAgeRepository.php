<?php

declare(strict_types=1);

namespace App\Repository;

class FriseAgeRepository extends BaseRepository
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

    /**
     * Âges dans l'ordre de la frise, avec leurs entrées (une seule requête).
     *
     * @return array<int, \App\Entity\FriseAge>
     */
    public function findFrise(): array
    {
        return $this
            ->createQueryBuilder('frise_age')
            ->leftJoin('frise_age.entrees', 'e')
            ->addSelect('e')
            ->orderBy('frise_age.ordre', 'ASC')
            ->addOrderBy('frise_age.id', 'ASC')
            ->addOrderBy('e.ordre', 'ASC')
            ->addOrderBy('e.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
