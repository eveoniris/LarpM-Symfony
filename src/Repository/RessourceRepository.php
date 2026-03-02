<?php

declare(strict_types=1);

namespace App\Repository;

class RessourceRepository extends BaseRepository
{
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
