<?php

declare(strict_types=1);

namespace App\Repository;

/**
 * LarpManager\Repository\GnRepository.
 *
 * @author kevin
 */
class GroupeEnemyRepository extends BaseRepository
{
    /**
     * Fourni touttes les guerres en cours.
     */
    /** @return array<int, \App\Entity\GroupeEnemy> */
    public function findByWar(): array
    {
        return $this
            ->getEntityManager()
            ->createQuery('SELECT ge FROM App\Entity\GroupeEnemy ge JOIN ge.groupeRelatedByGroupeId g WHERE ge.groupe_peace = false AND ge.groupe_enemy_peace = false ORDER BY g.nom ASC')
            ->getResult();
    }

    /**
     * Fourni toutes les demandes de paix.
     */
    /** @return array<int, \App\Entity\GroupeEnemy> */
    public function findByRequestPeace(): array
    {
        return $this
            ->getEntityManager()
            ->createQuery(
                'SELECT ge FROM App\Entity\GroupeEnemy ge JOIN ge.groupeRelatedByGroupeId g WHERE (ge.groupe_peace = false OR ge.groupe_enemy_peace = false) AND (ge.groupe_peace = true OR ge.groupe_enemy_peace = true) ORDER BY g.nom ASC',
            )
            ->getResult();
    }
}
