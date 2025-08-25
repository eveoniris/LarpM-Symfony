<?php

namespace App\Repository;

use App\Service\OrderBy;
use JetBrains\PhpStorm\Deprecated;

class TokenRepository extends BaseRepository
{
    /**
     * Fourni tous les tokens classé par ordre alphabétique.
     */
    // TODO use sortAttributes on a findAll()
    public function findAllOrderedByLabel()
    {
        return $this->findBy([], ['label' => 'ASC']);
    }

    public function searchAttributes(?string $alias = null): array
    {
        $alias ??= static::getEntityAlias();

        return [
            ...parent::searchAttributes(),
            $alias.'.label',
            $alias.'.tag',
            $alias.'.description',
        ];
    }

    public function sortAttributes(?string $alias = null): array
    {
        $alias ??= static::getEntityAlias();

        return [
            ...parent::sortAttributes($alias),
            'label' => [OrderBy::ASC => [$alias.'.label' => OrderBy::ASC], OrderBy::DESC => [$alias.'.label' => OrderBy::DESC]],
            'tag' => [OrderBy::ASC => [$alias.'.tag' => OrderBy::ASC], OrderBy::DESC => [$alias.'.tag' => OrderBy::DESC]],
            'description' => [OrderBy::ASC => [$alias.'.description' => OrderBy::ASC], OrderBy::DESC => [$alias.'.description' => OrderBy::DESC]],
        ];
    }

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
