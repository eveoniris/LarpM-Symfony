<?php


namespace App\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * LarpManager\Repository\GnRepository.
 *
 * @author kevin
 */
class GroupeAllieRepository extends BaseRepository
{
    /**
     * Fourni toutes les alliances en cours.
     */
    public function findByAlliances()
    {
        return $this->getEntityManager()
            ->createQuery('SELECT ga FROM App\Entity\GroupeAllie ga JOIN ga.groupeRelatedByGroupeId g  WHERE ga.groupe_accepted = true AND ga.groupe_allie_accepted = true ORDER BY g.nom ASC')
            ->getResult();
    }

    /**
     * Fourni toutes les demandes d'alliances.
     */
    public function findByDemandeAlliances()
    {
        return $this->getEntityManager()
            ->createQuery('SELECT ga FROM App\Entity\GroupeAllie ga JOIN ga.groupeRelatedByGroupeId g  WHERE ( ga.groupe_accepted = true OR ga.groupe_allie_accepted = true)  AND (ga.groupe_accepted = false OR ga.groupe_allie_accepted = false) ORDER BY g.nom ASC')
            ->getResult();
    }
}
