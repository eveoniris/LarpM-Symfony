<?php

declare(strict_types=1);

namespace App\Repository;

use App\Service\OrderBy;

/**
 * LarpManager\Repository\IngredientRepository.
 *
 * @author Kevin F.
 */
class IngredientRepository extends BaseRepository
{
    /**
     * Find all ingredients ordered by label.
     */
    /** @return array<int, \App\Entity\Ingredient> */
    public function findAllOrderedByLabel(): array
    {
        return $this->getEntityManager()->createQuery('SELECT i FROM App\Entity\Ingredient i ORDER BY i.label ASC, i.niveau ASC')->getResult();
    }

    /** @return array<string, array<string, mixed>> */
    public function sortAttributes(?string $alias = null): array
    {
        $alias ??= static::getEntityAlias();

        return [
            'label' => [
                OrderBy::ASC => [$alias . '.label' => OrderBy::ASC],
                OrderBy::DESC => [$alias . '.label' => OrderBy::DESC],
            ],
            'color' => [
                OrderBy::ASC => [$alias . '.niveau' => OrderBy::ASC],
                OrderBy::DESC => [$alias . '.niveau' => OrderBy::DESC],
            ],
            'niveau' => [
                OrderBy::ASC => [$alias . '.niveau' => OrderBy::ASC],
                OrderBy::DESC => [$alias . '.niveau' => OrderBy::DESC],
            ],
            'dose' => [
                OrderBy::ASC => [$alias . '.dose' => OrderBy::ASC],
                OrderBy::DESC => [$alias . '.dose' => OrderBy::DESC],
            ],
        ];
    }
}
