<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Age;
use App\Service\OrderBy;
use Doctrine\ORM\QueryBuilder;

class AgeRepository extends BaseRepository
{
    /**
     * Trouve tous les ages classé par index.
     */
    /** @return list<Age> */
    public function findAllOrderedByLabel(): array
    {
        return $this->getEntityManager()->createQuery('SELECT a FROM App\Entity\Age a ORDER BY a.label ASC')->getResult();
    }

    /**
     * Fourni tous les ages disponible à la création d'un personnage.
     */
    /** @return list<Age> */
    public function findAllOnCreation(): array
    {
        return $this->getEntityManager()->createQuery('SELECT a FROM App\Entity\Age a WHERE a.enableCreation = true ORDER BY a.label ASC')->getResult();
    }

    public function findOneByAgeReel(int $ageReel): ?Age
    {
        return $this
            ->createQueryBuilder('a')
            ->andWhere('a.minimumValue <= :ageReel')
            ->setParameter('ageReel', $ageReel)
            ->orderBy('a.minimumValue', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleResult();
    }

    public function enableCreation(QueryBuilder $query, bool $enable): QueryBuilder
    {
        return $query->andWhere($query->getRootAliases()[0] . '.enableCreation = :enable')->setParameter('enable', $enable);
    }

    /** @return array<int|string, string|array<string, mixed>|null> */
    public function searchAttributes(?string $alias = null, bool $withAlias = true): array
    {
        $alias ??= static::getEntityAlias();

        return [
            ...parent::searchAttributes(),
            $alias . '.label',
            $alias . '.description',
            $alias . '.bonus',
            $alias . '.enableCreation',
            $alias . '.minimumValue',
        ];
    }

    /** @return array<string, array<string, mixed>> */
    public function sortAttributes(?string $alias = null): array
    {
        $alias ??= static::getEntityAlias();

        return [
            ...parent::sortAttributes($alias),
            'minimumValue' => [
                OrderBy::ASC => [$alias . '.minimumValue' => OrderBy::ASC],
                OrderBy::DESC => [$alias . '.minimumValue' => OrderBy::DESC],
            ],
            'bonus' => [
                OrderBy::ASC => [$alias . '.bonus' => OrderBy::ASC],
                OrderBy::DESC => [$alias . '.bonus' => OrderBy::DESC],
            ],
            'enableCreation' => [
                OrderBy::ASC => [$alias . '.enableCreation' => OrderBy::ASC],
                OrderBy::DESC => [$alias . '.enableCreation' => OrderBy::DESC],
            ],
            'label' => [
                OrderBy::ASC => [$alias . '.label' => OrderBy::ASC],
                OrderBy::DESC => [$alias . '.label' => OrderBy::DESC],
            ],
        ];
    }

    /** @return array<string, string> */
    public function translateAttributes(): array
    {
        return [
            ...parent::translateAttributes(),
            'description' => $this->translator->trans('Description', domain: 'repository'),
            'label' => $this->translator->trans('Libellé', domain: 'repository'),
            'bonus' => $this->translator->trans('Bonus', domain: 'repository'),
            'enableCreation' => $this->translator->trans('Autorisé à la création', domain: 'repository'),
            'minimumValue' => $this->translator->trans('Age minimum de la tranche', domain: 'repository'),
        ];
    }
}
