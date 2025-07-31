<?php

namespace App\Repository;

use App\Entity\QrCodeScanLog;
use App\Service\OrderBy;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class QrCodeScanLogRepository extends BaseRepository
{
    public function findAll(): array
    {
        return $this->findBy([], ['date' => 'DESC']);
    }

    public function search(
        mixed             $search = null,
        string|array|null $attributes = self::SEARCH_NOONE,
        ?OrderBy          $orderBy = null,
        ?string           $alias = null,
        ?QueryBuilder     $query = null
    ): QueryBuilder
    {
        $alias ??= static::getEntityAlias();
        $query ??= $this->createQueryBuilder($alias);
        $query->join($alias . '.user', 'u');
        $query->join($alias . '.participant', 'pt');
        $query->join('pt.personnage', 'p');
        $query->join($alias . '.item', 'i');

        return parent::search($search, $attributes, $orderBy, $alias, $query);
    }

    public function searchAttributes(): array
    {
        $alias ??= static::getEntityAlias();

        return [
            self::SEARCH_ALL,
            $alias . '.date',
            'p.nom as participant_nom',
            'u.username as username',
            'i.numero as numero',
            'i.label as objet_nom',
        ];
    }

    public function sortAttributes(?string $alias = null): array
    {
        $alias ??= static::getEntityAlias();

        return [
            ...parent::sortAttributes($alias),
            $alias . '.date' => [
                OrderBy::ASC => [$alias . '.date' => OrderBy::ASC],
                OrderBy::DESC => [$alias . '.date' => OrderBy::DESC],
            ],
            $alias . '.description' => [
                OrderBy::ASC => [$alias . '.description' => OrderBy::ASC],
                OrderBy::DESC => [$alias . '.description' => OrderBy::DESC],
            ],
            'p.nom' => [
                OrderBy::ASC => ['p.nom' => OrderBy::ASC],
                OrderBy::DESC => ['p.nom' => OrderBy::DESC],
            ],
            'u.username' => [
                OrderBy::ASC => ['u.username' => OrderBy::ASC],
                OrderBy::DESC => ['u.username' => OrderBy::DESC],
            ],
            'i.numero' => [
                OrderBy::ASC => ['i.numero' => OrderBy::ASC],
                OrderBy::DESC => ['i.numero' => OrderBy::DESC],
            ],
            'i.label' => [
                OrderBy::ASC => ['i.label' => OrderBy::ASC],
                OrderBy::DESC => ['i.label' => OrderBy::DESC],
            ],
        ];
    }

    public function translateAttribute(string $attribute): string
    {
        $attribute = match ($this->getAttributeAsName($attribute)) {
            'participant_nom', 'p.nom' => 'participant_nom',
            'username', 'u.username' => 'username',
            'i.numero', 'numero' => 'numero',
            'i.label', 'objet_nom' => 'objet_nom',
            default => $attribute,
        };

        return parent::translateAttribute($attribute);
    }

    public function translateAttributes(): array
    {
        return [
            ...parent::translateAttributes(),
            'participant_nom' => $this->translator->trans("Nom d'uitlisateur", domain: 'repository'),
            'username' => $this->translator->trans("Pseudo", domain: 'repository'),
            'numero' => $this->translator->trans('Numéro', domain: 'repository'),
            'objet_nom' => $this->translator->trans("Nom de l'objet", domain: 'repository'),
        ];
    }
}
