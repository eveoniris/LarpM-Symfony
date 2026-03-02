<?php

declare(strict_types=1);

namespace App\Repository;

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
    /** @return array<int, \App\Entity\GroupeAllie> */
    public function findByAlliances(): array
    {
        return $this
            ->getEntityManager()
            ->createQuery('SELECT ga FROM App\Entity\GroupeAllie ga JOIN ga.groupeRelatedByGroupeId g  WHERE ga.groupe_accepted = true AND ga.groupe_allie_accepted = true ORDER BY g.nom ASC')
            ->getResult();
    }

    /**
     * Fourni toutes les demandes d'alliances.
     */
    /** @return array<int, \App\Entity\GroupeAllie> */
    public function findByDemandeAlliances(): array
    {
        return $this
            ->getEntityManager()
            ->createQuery(
                'SELECT ga FROM App\Entity\GroupeAllie ga JOIN ga.groupeRelatedByGroupeId g  WHERE ( ga.groupe_accepted = true OR ga.groupe_allie_accepted = true)  AND (ga.groupe_accepted = false OR ga.groupe_allie_accepted = false) ORDER BY g.nom ASC',
            )
            ->getResult();
    }
}
