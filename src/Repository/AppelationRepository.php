<?php

declare(strict_types=1);

namespace App\Repository;

/**
 * LarpManager\Repository\AppelationRepository.
 *
 * @author kevin
 */
class AppelationRepository extends BaseRepository
{
    /**
     * Fourni la liste des appelations n'étant pas dépendante d'une autre appelation.
     *
     * @return array<int, \App\Entity\Appelation>
     */
    public function findRoot()
    {
        return $this->getEntityManager()->createQuery('SELECT a FROM App\Entity\Appelation a WHERE a.appelation IS NULL')->getResult();
    }
}
