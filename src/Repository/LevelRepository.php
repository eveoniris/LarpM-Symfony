<?php

namespace App\Repository;

use App\Service\OrderBy;

class LevelRepository extends BaseRepository
{
    public function searchAttributes(?string $alias = null): array
    {
        $alias ??= static::getEntityAlias();

        return [
            ...parent::searchAttributes(),
            $alias.'.label',
            $alias.'.cout',
            $alias.'.cout_favori',
            $alias.'.cout_meconu',
            $alias.'.index',
        ];
    }

    public function sortAttributes(?string $alias = null): array
    {
        $alias ??= static::getEntityAlias();

        return [
            ...parent::sortAttributes($alias),
            'index' => [OrderBy::ASC => [$alias.'.index' => OrderBy::ASC], OrderBy::DESC => [$alias.'.index' => OrderBy::DESC]],
            'label' => [OrderBy::ASC => [$alias.'.label' => OrderBy::ASC], OrderBy::DESC => [$alias.'.label' => OrderBy::DESC]],
        ];
    }

    public function translateAttributes(): array
    {
        return [
            ...parent::translateAttributes(),
            'index' => $this->translator->trans('Niveau', domain: 'repository'),
            'label' => $this->translator->trans('Libellé', domain: 'repository'),
            'cout' => $this->translator->trans('cout XP', domain: 'repository'),
            'cout_favori' => $this->translator->trans('cout XP favori', domain: 'repository'),
            'cout_meconu' => $this->translator->trans('cout XP méconnu', domain: 'repository'),
        ];
    }
}
