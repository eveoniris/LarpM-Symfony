<?php


namespace App\Repository;

use App\Entity\Intrigue;
use App\Service\OrderBy;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

class IntrigueRepository extends BaseRepository
{
    /**
     * Recherche d'une liste d'intrigue.
     *
     * @param unknown $type
     * @param unknown $value
     * @param unknown $limit
     * @param unknown $offset
     */
    public function findList($type, $value, $limit, $offset, array $order = [])
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('i');
        $qb->from(Intrigue::class, 'i');
        if ($type && $value && 'titre' === $type) {
            $qb->andWhere('i.titre LIKE :value');
            $qb->setParameter('value', '%'.$value.'%');
        }

        $qb->setFirstResult($offset);
        $qb->setMaxResults($limit);
        $qb->orderBy('i.'.$order['by'], $order['dir']);

        return $qb->getQuery()->getResult();
    }

    /**
     * Trouve le nombre d'intrigue correspondant aux critères de recherche.
     */
    public function findCount($type, ?string $value)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select($qb->expr()->count('i'));
        $qb->from(Intrigue::class, 'i');

        if ($type && $value && 'titre' === $type) {
            $qb->andWhere('i.titre LIKE :value');
            $qb->setParameter('value', '%'.$value.'%');
        }

        return $qb->getQuery()->getSingleScalarResult();
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
        $query->join($alias.'.user', 'auteur');

        return parent::search($search, $attributes, $orderBy, $alias, $query);
    }

    public function searchAttributes(): array
    {
        $alias ??= static::getEntityAlias();

        return [
            ...parent::searchAttributes(),
            $alias.'.titre',
            'auteur.username as auteur',
            $alias.'.description', // => 'Description',
        ];
    }

    public function sortAttributes(?string $alias = null): array
    {
        $alias ??= static::getEntityAlias();

        return [
            ...parent::sortAttributes($alias),
            'titre' => [
                OrderBy::ASC => [$alias.'.titre' => OrderBy::ASC],
                OrderBy::DESC => [$alias.'.titre' => OrderBy::DESC],
            ],
            'description' => [
                OrderBy::ASC => [$alias.'.description' => OrderBy::ASC],
                OrderBy::DESC => [$alias.'.description' => OrderBy::DESC],
            ],
            'auteur' => [
                OrderBy::ASC => ['auteur.username' => OrderBy::ASC],
                OrderBy::DESC => ['auteur.username' => OrderBy::DESC],
            ],
        ];
    }

    public function translateAttribute(string $attribute): string
    {
        $attribute = match ($this->getAttributeAsName($attribute)) {
            'user_id', 'user', 'auteur' => 'auteur',
            default => $attribute,
        };

        return parent::translateAttribute($attribute);
    }

    public function translateAttributes(): array
    {
        return [
            ...parent::translateAttributes(),
            'description' => $this->translator->trans('Description', domain: 'repository'),
            'titre' => $this->translator->trans('Titre', domain: 'repository'),
            'auteur' => $this->translator->trans('Auteur', domain: 'repository'),
        ];
    }
}
