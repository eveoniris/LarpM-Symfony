<?php

declare(strict_types=1);

namespace App\Repository;

use App\Service\OrderBy;

class TokenRepository extends BaseRepository
{
    /**
     * Fourni tous les tokens classé par ordre alphabétique.
     */
    // TODO use sortAttributes on a findAll()
    /** @return array<int, \App\Entity\Token> */
    public function findAllOrderedByLabel(): array
    {
        /** @var array<int, \App\Entity\Token> */
        return $this->findBy([], ['label' => 'ASC']);
    }

    /** @return array<int|string, string|array<string, mixed>|null> */
    public function searchAttributes(?string $alias = null, bool $withAlias = true): array
    {
        $alias ??= static::getEntityAlias();

        return [
            ...parent::searchAttributes($alias, $withAlias),
            $alias . '.label',
            $alias . '.tag',
            $alias . '.description',
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
            'tag' => [
                OrderBy::ASC => [$alias . '.tag' => OrderBy::ASC],
                OrderBy::DESC => [$alias . '.tag' => OrderBy::DESC],
            ],
            'description' => [
                OrderBy::ASC => [$alias . '.description' => OrderBy::ASC],
                OrderBy::DESC => [$alias . '.description' => OrderBy::DESC],
            ],
        ];
    }

    /** @return array<string, string> */
    public function translateAttributes(): array
    {
        return [
            ...parent::translateAttributes(),
            'label' => $this->translator->trans('Libellé', domain: 'repository'),
            'tag' => $this->translator->trans('Tag', domain: 'repository'),
            'description' => $this->translator->trans('Description', domain: 'repository'),
        ];
    }
}
