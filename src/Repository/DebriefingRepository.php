<?php

namespace App\Repository;

use App\Service\OrderBy;
use Doctrine\ORM\QueryBuilder;

class DebriefingRepository extends BaseRepository
{
    /**
     * Trouve les debriefing correspondant aux critères de recherche.
     */
    /*public function findCount(array $criteria = [])
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select($qb->expr()->count('d'));
        $qb->from(\App\Entity\Debriefing::class, 'd');

        foreach ($criteria as $criter) {
            $qb->andWhere('?1');
            $qb->setParameter(1, $criter);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }*/
    /*
        public function findList(?string $type, $value, array $order = [], int $limit = 50, int $offset = 0)
        {
            $qb = $this->getQueryBuilder($type, $value, $order);
            $qb->setFirstResult($offset);
            $qb->setMaxResults($limit);
            $qb->orderBy('d.'.$order['by'], $order['dir']);

            return $qb->getQuery();
        }*/

    protected function getQueryBuilder(?string $type, $value, array $order = []): QueryBuilder
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('d');
        $qb->from(\App\Entity\Debriefing::class, 'd');

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

        return $qb;
    }

    public function search(
        mixed $search = null,
        null|string|array $attributes = self::SEARCH_NOONE,
        OrderBy $orderBy = null,
        string $alias = null,
        QueryBuilder $query = null
    ): QueryBuilder {
        $alias ??= static::getEntityAlias();
        $query ??= $this->createQueryBuilder($alias);


        $query->join($alias.'.groupe', 'groupe');
        $query->leftJoin($alias.'.player', 'player');
        $query->join($alias.'.user', 'user');

        return parent::search($search, $attributes, $orderBy, $alias, $query);
    }

    public function searchAttributes(string $alias = null): array
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

    public function sortAttributes(string $alias = null): array
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
