<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Intrigue;
use App\Service\OrderBy;
use Doctrine\ORM\QueryBuilder;

class IntrigueRepository extends BaseRepository
{
    /**
     * Recherche d'une liste d'intrigue.
     */
    /**
     * @param array<string, string> $order
     *
     * @return array<int, Intrigue>
     */
    public function findList(?string $type, mixed $value, int $limit, int $offset, array $order = []): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('i');
        $qb->from(Intrigue::class, 'i');
        if ($type && $value && 'titre' === $type) {
            $qb->andWhere('i.titre LIKE :value');
            $qb->setParameter('value', '%' . $value . '%');
        }

        $qb->setFirstResult((int) $offset);
        $qb->setMaxResults((int) $limit);
        $qb->orderBy('i.' . $order['by'], $order['dir']);

        return $qb->getQuery()->getResult();
    }

    /**
     * Trouve le nombre d'intrigue correspondant aux critères de recherche.
     */
    public function findCount(string $type, ?string $value): int
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select($qb->expr()->count('i'));
        $qb->from(Intrigue::class, 'i');

        if ($type && $value && 'titre' === $type) {
            $qb->andWhere('i.titre LIKE :value');
            $qb->setParameter('value', '%' . $value . '%');
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /** @param string|array<int|string, string|array<string, mixed>|null>|null $attributes */
    public function search(
        mixed $search = null,
        string|array|null $attributes = self::SEARCH_NOONE,
        ?OrderBy $orderBy = null,
        ?string $alias = null,
        ?QueryBuilder $query = null,
    ): QueryBuilder {
        $alias ??= static::getEntityAlias();
        $query ??= $this->createQueryBuilder($alias);
        $query->join($alias . '.user', 'auteur');

        return parent::search($search, $attributes, $orderBy, $alias, $query);
    }

    /** @return array<string, array<string, mixed>> */
    public function searchAttributes(?string $alias = null, bool $withAlias = true): array
    {
        $alias ??= static::getEntityAlias();

        return [
            ...parent::searchAttributes(),
            $alias . '.titre',
            'auteur.username as auteur',
            $alias . '.description', // => 'Description',
        ];
    }

    /** @return array<string, array<string, mixed>> */
    public function sortAttributes(?string $alias = null): array
    {
        $alias ??= static::getEntityAlias();

        return [
            ...parent::sortAttributes($alias),
            'titre' => [
                OrderBy::ASC => [$alias . '.titre' => OrderBy::ASC],
                OrderBy::DESC => [$alias . '.titre' => OrderBy::DESC],
            ],
            'description' => [
                OrderBy::ASC => [$alias . '.description' => OrderBy::ASC],
                OrderBy::DESC => [$alias . '.description' => OrderBy::DESC],
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

    /** @return array<string, string> */
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
