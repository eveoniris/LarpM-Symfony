<?php


namespace App\Repository;

use App\Service\OrderBy;
use Doctrine\ORM\EntityRepository;

/**
 * LarpManager\Repository\CompetenceFamilyRepository.
 *
 * @author kevin
 */
class CompetenceFamilyRepository extends BaseRepository
{
    /**
     * Find all classes ordered by label.
     *
     * @return ArrayCollection $competenceFamilies
     */
    public function findAllOrderedByLabel()
    {
        return $this->getEntityManager()
            ->createQuery('SELECT cf FROM App\Entity\CompetenceFamily cf ORDER BY cf.label ASC')
            ->getResult();
    }

    public function sortAttributes(string $alias = null): array
    {
        $alias ??= static::getEntityAlias();

        return [
            'label' => [
                OrderBy::ASC => [$alias.'.label' => OrderBy::ASC],
                OrderBy::DESC => [$alias.'.label' => OrderBy::DESC],
            ],
        ];
    }
}
