<?php


namespace App\Repository;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityRepository;

/**
 * LarpManager\Repository\JoueurRepository.
 *
 * @author kevin
 */
class JoueurRepository extends BaseRepository
{
    /**
     * Recherche à partir du prénom.
     *
     * @return ArrayCollection $joueur
     */
    public function findByFirstName(string $firstName): ArrayCollection
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('j')
            ->from('App\Entity\Joueur', 'j')
            ->where($qb->expr()->like('j.prenom', $qb->expr()->literal('%'.$firstName.'%')))
            ->orderBy('j.prenom', 'ASC');

        $result = $qb->getQuery()->getResult();

        return new ArrayCollection($result);
    }

    /**
     * Recherche à partir du nom de famille.
     *
     * @return ArrayCollection $joueur
     */
    public function findByLastName(string $lastName): ArrayCollection
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('j')
            ->from('App\Entity\Joueur', 'j')
            ->where($qb->expr()->like('j.nom', $qb->expr()->literal('?1')))
            ->setParameter(1, '%'.$lastName.'%')
            ->orderBy('j.nom', 'ASC');

        $result = $qb->getQuery()->getResult();

        return new ArrayCollection($result);
    }
}
