<?php

namespace App\Repository;

use App\Entity\Rumeur;
use App\Service\OrderBy;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;

class RumeurRepository extends BaseRepository
{
    /**
     * Recherche d'une liste de rumeur.
     */
    public function findList($type, $value, array $order = [], int $limit = 0, int $offset = 20): Query
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('i');
        $qb->from(Rumeur::class, 'i');
        if ($value && 'text' === $type) {
            $qb->andWhere('i.text LIKE :value');
            $qb->setParameter('value', '%'.$value.'%');
        }
        if ($value && 'territoire' === $type) {
            $qb->join('i.territoire', 't');
            $qb->andWhere('t.nom LIKE :value');
            $qb->setParameter('value', '%'.$value.'%');
        }

        $qb->setFirstResult($offset);
        $qb->setMaxResults($limit);
        $qb->orderBy('i.'.$order['by'], $order['dir']);

        return $qb->getQuery();
    }

    /**
     * Trouve le nombre de rumeurs correspondant aux critères de recherche.
     */
    public function findCount($type, ?string $value): float|bool|int|string|null
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select($qb->expr()->count('r'));
        $qb->from(Rumeur::class, 'r');

        if ($value && 'text' === $type) {
            $qb->andWhere('r.text LIKE :value');
            $qb->setParameter('value', '%'.$value.'%');
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function search(
        mixed $search = null,
        string|array|null $attributes = self::SEARCH_NOONE,
        ?OrderBy $orderBy = null,
        ?string $alias = null,
        ?QueryBuilder $query = null,
    ): QueryBuilder {
        $alias ??= static::getEntityAlias();
        $query ??= $this->createQueryBuilder($alias);

        $query->join($alias.'.user', 'user');
        $query->join($alias.'.territoire', 'territoire');

        return parent::search($search, $attributes, $orderBy, $alias, $query);
    }

    public function searchAttributes(): array
    {
        $alias ??= static::getEntityAlias();

        return [
            self::SEARCH_ALL,
            $alias.'.text',
            'user.username as user',
            'territoire.nom as territoire',
        ];
    }

    public function sortAttributes(?string $alias = null): array
    {
        $alias ??= static::getEntityAlias();

        return [
            ...parent::sortAttributes($alias),

            $alias.'.text' => [
                OrderBy::ASC => [$alias.'.text' => OrderBy::ASC],
                OrderBy::DESC => [$alias.'.text' => OrderBy::DESC],
            ],
            'user.username' => [
                OrderBy::ASC => ['user.username' => OrderBy::ASC],
                OrderBy::DESC => ['user.username' => OrderBy::DESC],
            ],
            'territoire.nom' => [
                OrderBy::ASC => ['territoire.nom' => OrderBy::ASC],
                OrderBy::DESC => ['territoire.nom' => OrderBy::DESC],
            ],
        ];
    }

    public function translateAttribute(string $attribute): string
    {
        $attribute = match ($this->getAttributeAsName($attribute)) {
            'user_id', 'user' => 'user',
            'territoire_id', 'territoire' => 'territoire',
            default => $attribute,
        };

        return parent::translateAttribute($attribute);
    }

    public function translateAttributes(): array
    {
        return [
            ...parent::translateAttributes(),
            'user' => $this->translator->trans('Auteur', domain: 'repository'),
            'text' => $this->translator->trans('Contenu', domain: 'repository'),
            'territoire' => $this->translator->trans('Territoire', domain: 'repository'),
        ];
    }
}
