<?php

declare(strict_types=1);

namespace App\Repository;

use App\Service\OrderBy;

/**
 * LarpManager\Repository\AttributeTypeRepository.
 *
 * @author jsy
 */
class AttributeTypeRepository extends BaseRepository
{
    /**
     * Find all classes ordered by label.
     */
    /** @return array<int, \App\Entity\AttributeType> */
    public function findAllOrderedByLabel(): array
    {
        return $this->getEntityManager()->createQuery('SELECT cf FROM App\Entity\AttributeType cf ORDER BY cf.label ASC')->getResult();
    }

    /** @return array<string, array<string, mixed>> */
    public function searchAttributes(?string $alias = null, bool $withAlias = true): array
    {
        $alias ??= static::getEntityAlias();

        return [
            ...parent::searchAttributes(),
            $alias . '.id',
            $alias . '.label',
        ];
    }

    /** @return array<string, array<string, mixed>> */
    public function sortAttributes(?string $alias = null): array
    {
        $alias ??= static::getEntityAlias();

        return [
            ...parent::sortAttributes($alias),
            'label' => [
                OrderBy::ASC => [$alias . '.label' => OrderBy::ASC],
                OrderBy::DESC => [$alias . '.label' => OrderBy::DESC],
            ],
        ];
    }

    /** @return array<string, string> */
    public function translateAttributes(): array
    {
        return [
            ...parent::translateAttributes(),
            'label' => $this->translator->trans('Libellé', domain: 'repository'),
        ];
    }
}
