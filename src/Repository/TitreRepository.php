<?php


namespace App\Repository;

use App\Service\OrderBy;

/**
 * LarpManager\Repository\TitreRepository.
 *
 * @author kevin
 */
class TitreRepository extends BaseRepository
{
    /**
     * Trouve tous les titres classé par renommé.
     *
     * @return ArrayCollection $sorts
     */
    public function findByRenomme()
    {
        return $this->getEntityManager()
            ->createQuery('SELECT t FROM App\Entity\Titre t ORDER BY t.renomme ASC')
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
            'renomme' => [
                OrderBy::ASC => [$alias.'.renomme' => OrderBy::ASC],
                OrderBy::DESC => [$alias.'.renomme' => OrderBy::DESC],
            ],
        ];
    }
}
