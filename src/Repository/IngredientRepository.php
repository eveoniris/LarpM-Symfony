<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * LarpManager\Repository\IngredientRepository.
 *
 * @author Kevin F.
 */
class IngredientRepository extends EntityRepository
{
    /**
     * Find all ingredients ordered by label.
     *
     * @return ArrayCollection $ingredients
     */
    public function findAllOrderedByLabel()
    {
        return $this->getEntityManager()
            ->createQuery('SELECT i FROM App\Entity\Ingredient i ORDER BY i.label ASC, i.niveau ASC')
            ->getResult();
    }
}
