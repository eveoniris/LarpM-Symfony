<?php


namespace App\Repository;

class RessourceRepository extends BaseRepository
{
    /**
     * Fourni la liste des ressources par ordre alphabétique.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function findAllOrderByLabel()
    {
        $query = $this->getEntityManager()->createQuery('SELECT r FROM App\Entity\Ressource r ORDER BY r.label ASC');

        return $query->getResult();
    }

    /**
     * Fourni la liste des ressources communes.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function findCommun()
    {
        $query = $this->getEntityManager()->createQuery(
            'SELECT r FROM App\Entity\Ressource r JOIN r.rarete ra WHERE ra.label LIKE \'Commun\' ORDER BY r.label ASC',
        );

        return $query->getResult();
    }

    /**
     * Fourni la liste des ressources rares.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function findRare()
    {
        $query = $this->getEntityManager()->createQuery(
            'SELECT r FROM App\Entity\Ressource r JOIN r.rarete ra WHERE ra.label LIKE \'Rare\' ORDER BY r.label ASC',
        );

        return $query->getResult();
    }
}
