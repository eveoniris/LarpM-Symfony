<?php

namespace App\Repository;

use App\Service\OrderBy;
use Doctrine\ORM\EntityRepository;

/**
 * LarpManager\Repository\IngredientRepository.
 *
 * @author Kevin F.
 */
class IngredientRepository extends BaseRepository
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

    public function sortAttributes(?string $alias = null): array
    {
        $alias ??= static::getEntityAlias();

        return [
            'label' => [
                OrderBy::ASC => [$alias.'.label' => OrderBy::ASC],
                OrderBy::DESC => [$alias.'.label' => OrderBy::DESC],
            ],
            'color' => [
                OrderBy::ASC => [$alias.'.niveau' => OrderBy::ASC],
                OrderBy::DESC => [$alias.'.niveau' => OrderBy::DESC],
            ],
            'niveau' => [
                OrderBy::ASC => [$alias.'.niveau' => OrderBy::ASC],
                OrderBy::DESC => [$alias.'.niveau' => OrderBy::DESC],
            ],
            'dose' => [
                OrderBy::ASC => [$alias.'.dose' => OrderBy::ASC],
                OrderBy::DESC => [$alias.'.dose' => OrderBy::DESC],
            ],
        ];
    }
}
