<?php


namespace App\Repository;

use Doctrine\ORM\EntityRepository;

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
    public function findByWar()
    {
        return $this->getEntityManager()
            ->createQuery('SELECT ge FROM App\Entity\GroupeEnemy ge JOIN ge.groupeRelatedByGroupeId g WHERE ge.groupe_peace = false AND ge.groupe_enemy_peace = false ORDER BY g.nom ASC')
            ->getResult();
    }

    /**
     * Fourni toutes les demandes de paix.
     */
    public function findByRequestPeace()
    {
        return $this->getEntityManager()
            ->createQuery('SELECT ge FROM App\Entity\GroupeEnemy ge JOIN ge.groupeRelatedByGroupeId g WHERE (ge.groupe_peace = false OR ge.groupe_enemy_peace = false) AND (ge.groupe_peace = true OR ge.groupe_enemy_peace = true) ORDER BY g.nom ASC')
            ->getResult();
    }
}
