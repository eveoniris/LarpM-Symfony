<?php


namespace App\Repository;

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
}
