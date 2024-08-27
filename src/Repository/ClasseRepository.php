<?php


namespace App\Repository;

use App\Entity\Classe;
use App\Entity\Personnage;
use App\Service\OrderBy;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

/**
 * LarpManager\Repository\ClasseRepository.
 *
 * @author kevin
 */
class ClasseRepository extends BaseRepository
{
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

    /**
     * Returns a query builder to find all competences ordered by label.
     */
    public function getQueryBuilderFindAllOrderedByLabel(): QueryBuilder
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select('c')
            ->from(\App\Entity\Classe::class, 'c')
            ->addOrderBy('c.label_feminin')
            ->addOrderBy('c.label_masculin');
    }

    /**
     * Trouve toutes les classes disponibles à la création d'un personnage.
     */
    public function findAllCreation()
    {
        return $this->getEntityManager()
            ->createQuery('SELECT c FROM App\Entity\Classe c WHERE c.creation = true ORDER BY c.label_masculin ASC')
            ->getResult();
    }

    public function search(
        mixed $search = null,
        string|array|null $attributes = self::SEARCH_NOONE,
        ?OrderBy $orderBy = null,
        ?string $alias = null,
        ?QueryBuilder $query = null
    ): QueryBuilder {
        $alias ??= static::getEntityAlias();
        $orderBy ??= $this->orderBy;

        if ('creation' === $attributes) {
            $query = $this->createQueryBuilder($alias)
                ->orderBy($orderBy->getSort(), $orderBy->getOrderBy());

            return $this->creation(
                $query,
                filter_var($search, FILTER_VALIDATE_BOOLEAN)
            );
        }

        return parent::search($search, $attributes, $orderBy, $alias, $query);
    }

    public function creation(QueryBuilder $query, bool $creation): QueryBuilder
    {
        $query->andWhere($this->alias.'.creation = :value');

        return $query->setParameter('value', $creation);
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
}
