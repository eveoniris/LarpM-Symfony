<?php

declare(strict_types=1);

namespace App\Repository;

use App\Service\OrderBy;

class RessourceRepository extends BaseRepository
{
    /** @return array<int, string> */
    public function searchAttributes(?string $alias = null, bool $withAlias = true): array
    {
        $alias ??= static::getEntityAlias();

        return [
            self::SEARCH_ALL,
            $alias . '.label',
        ];
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

    /**
     * Fourni la liste des ressources par ordre alphabétique.
     *
     * @return list<\App\Entity\Ressource>
     */
    public function findAllOrderByLabel()
    {
        $query = $this->getEntityManager()->createQuery('SELECT r FROM App\Entity\Ressource r ORDER BY r.label ASC');

        return $query->getResult();
    }

    /**
     * Fourni la liste des ressources communes.
     *
     * @return list<\App\Entity\Ressource>
     */
    public function findCommun()
    {
        $query = $this->getEntityManager()->createQuery('SELECT r FROM App\Entity\Ressource r JOIN r.rarete ra WHERE ra.label LIKE \'Commun\' ORDER BY r.label ASC');

        return $query->getResult();
    }

    /**
     * Fourni la liste des ressources rares.
     *
     * @return list<\App\Entity\Ressource>
     */
    public function findRare()
    {
        $query = $this->getEntityManager()->createQuery('SELECT r FROM App\Entity\Ressource r JOIN r.rarete ra WHERE ra.label LIKE \'Rare\' ORDER BY r.label ASC');

        return $query->getResult();
    }
}
