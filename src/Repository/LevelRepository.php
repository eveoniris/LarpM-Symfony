<?php

namespace App\Repository;

use App\Service\OrderBy;
use Doctrine\ORM\QueryBuilder;

class LevelRepository extends BaseRepository
{
    public function searchAttributes(string $alias = null): array
    {
        $alias ??= static::getEntityAlias();

        return [
            ...parent::searchAttributes($alias),
            $alias.'.label',
            $alias.'.cout',
            $alias.'.cout_favori',
            $alias.'.cout_meconu',
            $alias.'.index',
        ];
    }

    public function sortAttributes(string $alias = null): array
    {
        $alias ??= static::getEntityAlias();

        return [
            ...parent::sortAttributes($alias),
            'index' => [OrderBy::ASC => [$alias.'.index' => OrderBy::ASC], OrderBy::DESC => [$alias.'.index' => OrderBy::DESC]],
            'label' => [OrderBy::ASC => [$alias.'.label' => OrderBy::ASC], OrderBy::DESC => [$alias.'.label' => OrderBy::DESC]],
            'cout' => [OrderBy::ASC => [$alias.'.cout' => OrderBy::ASC], OrderBy::DESC => [$alias.'.cout' => OrderBy::DESC]],
            'cout_favori' => [OrderBy::ASC => [$alias.'.cout_favori' => OrderBy::ASC], OrderBy::DESC => [$alias.'.cout_favori' => OrderBy::DESC]],
            'cout_meconu' => [OrderBy::ASC => [$alias.'.cout_meconu' => OrderBy::ASC], OrderBy::DESC => [$alias.'.cout_meconu' => OrderBy::DESC]],
       ];
    }

    public function translateAttributes(): array
    {
        return [
            ...parent::translateAttributes(),
            'index' => $this->translator->trans('Niveau'),
            'label' => $this->translator->trans('Libellé'),
            'cout' => $this->translator->trans('cout XP'),
            'cout_favori' => $this->translator->trans('cout XP favori'),
            'cout_meconu' => $this->translator->trans('cout XP méconnu'),
        ];
    }
}
