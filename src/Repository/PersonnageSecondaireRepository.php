<?php


namespace App\Repository;

use Doctrine\ORM\EntityRepository;

class PersonnageSecondaireRepository extends BaseRepository
{
    /**
     * Trouve tous les personnages secondaires.
     *
     * @return ArrayCollection $personnageSecondaire
     */
    public function findAll(): array
    {
        return $this->getEntityManager()
            ->createQuery('SELECT ps FROM App\Entity\PersonnageSecondaire ps')
            ->getResult();
    }
}
