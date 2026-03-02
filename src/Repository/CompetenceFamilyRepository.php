<?php

declare(strict_types=1);

namespace App\Repository;

use App\Service\OrderBy;

/**
 * LarpManager\Repository\CompetenceFamilyRepository.
 *
 * @author kevin
 */
class CompetenceFamilyRepository extends BaseRepository
{
    /**
     * Find all classes ordered by label.
     */
    /** @return array<int, \App\Entity\CompetenceFamily> */
    public function findAllOrderedByLabel(): array
    {
        return $this->getEntityManager()->createQuery('SELECT cf FROM App\Entity\CompetenceFamily cf ORDER BY cf.label ASC')->getResult();
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
        ];
    }
}
