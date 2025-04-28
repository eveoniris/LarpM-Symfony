<?php

namespace App\Repository;

use App\Service\OrderBy;
use Doctrine\ORM\QueryBuilder;

class ExperienceUsageRepository extends BaseRepository
{
    public function search(
        mixed $search = null,
        string|array|null $attributes = self::SEARCH_NOONE,
        ?OrderBy $orderBy = null,
        ?string $alias = null,
        ?QueryBuilder $query = null,
    ): QueryBuilder {
        $alias ??= static::getEntityAlias();
        $query ??= $this->createQueryBuilder($alias);


        return parent::search($search, $attributes, $orderBy, $alias, $query);
    }
}
