<?php

namespace App\Repository;

use App\Entity\GroupeGn;
use App\Entity\Territoire;
use App\Service\OrderBy;
use Doctrine\ORM\QueryBuilder;

class StrategieRepository extends BaseRepository
{
    protected function getStrategieQueryBuilder(int $gnId, ?string $type, $value, array $order = []): QueryBuilder
    {
        // Liste des groupes du GN en paramètre
        $qbGroupes = $this->getEntityManager()->createQueryBuilder();
        $qbGroupes->select('g');
        $qbGroupes->from(GroupeGn::class, 'g');
        $qbGroupes->where('g.gn = :value');
        $qbGroupes->setParameter('value', $gnId);

        $qbGroupes->getQuery()->getResult();

        return $qbGroupes;
        /*
                $qb = $this->getEntityManager()->createQueryBuilder();

                $qb->select('t');
                $qb->from(Territoire::class, 't');

                $qb->join('t.groupe', 'g');
                $qb->andWhere('g.id = :value');
                $qb->setParameter('value', $gnId);

                $qb->join('t.groupe', 'g');
                $qb->andWhere('g.id = :value');
                $qb->setParameter('value', $gnId);

                // retire les caractères non imprimable d'une chaine UTF-8
                $value = preg_replace('/[\x00-\x1F\x7F\xA0]/u', '', htmlspecialchars((string)$value));

                if ($type && $value) {
                    switch ($type) {
                        case 'Auteur':
                            $qb->join('d.player', 'u');
                            $qb->andWhere('u.username LIKE :value');
                            $qb->setParameter('value', '%'.$value.'%');
                            break;
                        case 'Scenariste':
                            $qb->join('d.user', 'u');
                            $qb->andWhere('u.username LIKE :value');
                            $qb->setParameter('value', '%'.$value.'%');
                            break;
                        case 'Groupe':
                            $qb->join('d.groupe', 'g');
                            $qb->andWhere('g.nom LIKE :value');
                            $qb->setParameter('value', '%'.$value.'%');
                            break;
                    }
                }

                return $qb;*/
    }

    public function search(
        mixed $search = null,
        string|array|null $attributes = self::SEARCH_NOONE,
        ?OrderBy $orderBy = null,
        ?string $alias = null,
        ?QueryBuilder $query = null
    ): QueryBuilder {
        $alias ??= static::getEntityAlias();
        $query ??= $this->createQueryBuilder($alias);

        $query->join($alias.'.groupe', 'groupe');
        $query->leftJoin($alias.'.player', 'player');
        $query->join($alias.'.user', 'user');

        return parent::search($search, $attributes, $orderBy, $alias, $query);
    }

    public function searchAttributes(?string $alias = null): array
    {
        $alias ??= static::getEntityAlias();

        return [
            ...parent::searchAttributes($alias),
            $alias.'.titre',
            'groupe.nom as groupe',
            'user.username as scriptwriter',
            'player.username as auteur',
        ];
    }

    public function sortAttributes(?string $alias = null): array
    {
        $alias ??= static::getEntityAlias();

        return [
            'gn' => [OrderBy::DESC => [$alias.'.gn' => OrderBy::ASC], OrderBy::ASC => [$alias.'.gn' => OrderBy::DESC]],
            'groupe' => [
                OrderBy::ASC => [$alias.'.groupe' => OrderBy::ASC],
                OrderBy::DESC => [$alias.'.groupe' => OrderBy::DESC],
            ],
            ...parent::sortAttributes($alias),
            // using alias like "scriptwriter" require another template loop due to the added $query->select() entity
            'user.username' => [OrderBy::ASC => ['user.username' => OrderBy::ASC], OrderBy::DESC => ['user.username' => OrderBy::DESC]],
            'player.username' => [OrderBy::ASC => ['player.username' => OrderBy::ASC], OrderBy::DESC => ['player.username' => OrderBy::DESC]],
        ];
    }

    public function translateAttribute(string $attribute): string
    {
        $attribute = match ($this->getAttributeAsName($attribute)) {
            'user_id', 'scriptwriter' => 'user',
            'groupe_id' => 'groupe',
            'player_id', 'auteur' => 'player',
            default => $attribute,
        };

        return parent::translateAttribute($attribute);
    }

    public function translateAttributes(): array
    {
        return [
            ...parent::translateAttributes(),
            'titre' => $this->translator->trans('Titre'),
            'groupe' => $this->translator->trans('Groupe'),
            'user' => $this->translator->trans('Scénariste'),
            'player' => $this->translator->trans('Auteur'),
        ];
    }
}
