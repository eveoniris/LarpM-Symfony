<?php

namespace App\Repository;

use App\Service\OrderBy;
use Doctrine\ORM\QueryBuilder;

class SphereRepository extends BaseRepository
{
    public function search(
        mixed $search = null,
        string|array|null $attributes = self::SEARCH_NOONE,
        ?OrderBy $orderBy = null,
        ?string $alias = null,
        ?QueryBuilder $query = null
    ): QueryBuilder {
        return parent::search(
            $search,
            $attributes,
            $orderBy ?? $this->orderBy,
            $alias ?? static::getEntityAlias()
        );
    }

    public function searchAttributes(?string $alias = null): array
    {
        $alias ??= static::getEntityAlias();

        return [
            ...parent::searchAttributes($alias),
            $alias.'.label',
        ];
    }

    public function sortAttributes(?string $alias = null): array
    {
        $alias ??= static::getEntityAlias();

        return [
            ...parent::sortAttributes($alias),
            'label' => [OrderBy::ASC => [$alias.'.label' => OrderBy::ASC], OrderBy::DESC => [$alias.'.label' => OrderBy::DESC]],
        ];
    }
}
