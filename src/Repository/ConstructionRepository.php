<?php

namespace App\Repository;

use App\Entity\Construction;
use App\Entity\Territoire;
use App\Service\OrderBy;
use Doctrine\ORM\QueryBuilder;

/**
 * LarpManager\Repository\ConstructionRepository.
 *
 * @author Kevin F.
 */
class ConstructionRepository extends BaseRepository
{
    /**
     * Find all constructions ordered by label.
     *
     * @return ArrayCollection $constructions
     */
    public function findAllOrderedByLabel(): array
    {
        return $this->getEntityManager()
            ->createQuery('SELECT r FROM App\Entity\Construction r ORDER BY r.label ASC')
            ->getResult();
    }

    /**
     * Find all constructions ordered by label.
     *
     * @return ArrayCollection $constructions
     */
    public function findAll(): array
    {
        return $this->getEntityManager()
            ->createQuery('SELECT r FROM App\Entity\Construction r ORDER BY r.label ASC')
            ->getResult();
    }

    public function searchAttributes(): array
    {
        $alias ??= static::getEntityAlias();

        return [
            self::SEARCH_ALL,
            $alias.'.label', // => 'Libellé',
            $alias.'.description', // => 'Description',
            $alias.'.defense',
        ];
    }

    public function sortAttributes(?string $alias = null): array
    {
        $alias ??= static::getEntityAlias();

        return [
            'label' => [
                OrderBy::ASC => [$alias.'.label' => OrderBy::ASC],
                OrderBy::DESC => [$alias.'.label' => OrderBy::DESC],
            ],
            'defense' => [
                OrderBy::ASC => [$alias.'.defense' => OrderBy::ASC],
                OrderBy::DESC => [$alias.'.defense' => OrderBy::DESC],
            ],
        ];
    }

    public function translateAttributes(): array
    {
        $attributes = parent::translateAttributes();
        unset($attributes['id']);

        $attributes['defense'] = $this->translator->trans('Défense', domain: 'repository');
        $attributes['description'] = $this->translator->trans('Description', domain: 'repository');
        $attributes['label'] = $this->translator->trans('Nom', domain: 'repository');

        return $attributes;
    }

    public function getTerritoires(Construction $construction): QueryBuilder
    {
        /** @var TerritoireRepository $territoireRepository */
        $territoireRepository = $this->entityManager->getRepository(Territoire::class);

        return $territoireRepository->createQueryBuilder('t')
            ->innerJoin(Construction::class, 'c')
            ->where('c.id = :cid')
            ->setParameter('cid', $construction->getId());
    }
}
