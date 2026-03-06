<?php

declare(strict_types=1);

namespace App\Repository;

use App\Service\OrderBy;
use Doctrine\ORM\QueryBuilder;

/**
 * LarpManager\Repository\CompetenceFamilyRepository.
 *
 * @author kevin
 */
class CompetenceFamilyRepository extends BaseRepository
{
    /**
     * Find all classes ordered by label.
     */
    /** @return array<int, \App\Entity\CompetenceFamily> */
    public function findAllOrderedByLabel(): array
    {
        return $this->getEntityManager()->createQuery('SELECT cf, c, l FROM App\Entity\CompetenceFamily cf
             LEFT JOIN cf.competences c
             LEFT JOIN c.level l
             ORDER BY cf.label ASC')->getResult();
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
        $query->leftJoin($alias . '.competences', 'competence')->addSelect('competence');
        $query->leftJoin('competence.level', 'level')->addSelect('level');

        return parent::search($search, $attributes, $orderBy, $alias, $query);
    }

    /** @return array<string, array<string, mixed>> */
    public function sortAttributes(?string $alias = null): array
    {
        $alias ??= static::getEntityAlias();

        return [
            'label' => [
                OrderBy::ASC => [$alias . '.label' => OrderBy::ASC],
                OrderBy::DESC => [$alias . '.label' => OrderBy::DESC],
            ],
        ];
    }
}
