<?php

namespace App\Repository;

use App\Entity\Classe;
use App\Entity\Personnage;
use App\Service\OrderBy;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\QueryBuilder;

class ClasseRepository extends BaseRepository
{
    /**
     * Trouve toutes les classes disponibles à la création d'un personnage.
     */
    public function findAllCreation()
    {
        return $this->getEntityManager()
            ->createQuery('SELECT c FROM App\Entity\Classe c WHERE c.creation = true ORDER BY c.label_masculin ASC')
            ->getResult();
    }

    /**
     * Find all classes ordered by label.
     *
     * @return ArrayCollection $classes
     */
    public function findAllOrderedByLabel()
    {
        return $this->getEntityManager()
            ->createQuery('SELECT c FROM App\Entity\Classe c ORDER BY c.label_masculin ASC')
            ->getResult();
    }

    public function getPersonnages(Classe $classe): QueryBuilder
    {
        /** @var PersonnageRepository $personnageRepository */
        $personnageRepository = $this->entityManager->getRepository(Personnage::class);

        return $personnageRepository->createQueryBuilder('p')
            ->innerJoin('p.classe', 'c')
            ->where('c.id = :cid')
            ->setParameter('cid', $classe->getId());
    }

    /**
     * Returns a query builder to find all competences ordered by label.
     */
    public function getQueryBuilderFindAllOrderedByLabel(): QueryBuilder
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select('c')
            ->from(Classe::class, 'c')
            ->addOrderBy('c.label_feminin')
            ->addOrderBy('c.label_masculin');
    }

    public function search(
        mixed $search = null,
        string|array|null $attributes = self::SEARCH_NOONE,
        ?OrderBy $orderBy = null,
        ?string $alias = null,
        ?QueryBuilder $query = null,
    ): QueryBuilder {
        $alias ??= static::getEntityAlias();
        $orderBy ??= $this->orderBy;

        if ('creation' === $attributes) {
            $query = $this->createQueryBuilder($alias)
                ->orderBy($orderBy->getSort(), $orderBy->getOrderBy());

            return $this->creation(
                $query,
                filter_var($search, FILTER_VALIDATE_BOOLEAN),
            );
        }

        return parent::search($search, $attributes, $orderBy, $alias, $query);
    }

    public function creation(QueryBuilder $query, bool $creation): QueryBuilder
    {
        $query->andWhere($this->alias.'.creation = :value');

        return $query->setParameter('value', $creation);
    }

    public function sortAttributes(?string $alias = null): array
    {
        $alias ??= static::getEntityAlias();

        return [
            ...parent::sortAttributes($alias),
            $alias.'.label_masculin' => [
                OrderBy::ASC => [$alias.'.label_masculin' => OrderBy::ASC],
                OrderBy::DESC => [$alias.'.label_masculin' => OrderBy::DESC],
            ],
            $alias.'.label_feminin' => [
                OrderBy::ASC => [$alias.'.label_feminin' => OrderBy::ASC],
                OrderBy::DESC => [$alias.'.label_feminin' => OrderBy::DESC],
            ],
            $alias.'.description' => [
                OrderBy::ASC => [$alias.'.description' => OrderBy::ASC],
                OrderBy::DESC => [$alias.'.description' => OrderBy::DESC],
            ],
            'creation' => [
                OrderBy::ASC => [$alias.'.creation' => OrderBy::ASC],
                OrderBy::DESC => [$alias.'.creation' => OrderBy::DESC],
            ],
        ];
    }
}
