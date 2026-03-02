<?php

declare(strict_types=1);

namespace App\Repository;

class PersonnageSecondaireRepository extends BaseRepository
{
    /**
     * Trouve tous les personnages secondaires.
     */
    public function findAll(): array
    {
        return $this->getEntityManager()->createQuery('SELECT ps FROM App\Entity\PersonnageSecondaire ps')->getResult();
    }
}
