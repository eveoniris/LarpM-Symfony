<?php

declare(strict_types=1);

namespace App\Repository;

/**
 * LarpManager\Repository\ReligionLevelRepository.
 *
 * @author kevin
 */
class ReligionLevelRepository extends BaseRepository
{
    /**
     * trouve tous les niveaux de religion classé par index.
     */
    /** @return array<int, \App\Entity\ReligionLevel> */
    public function findAllOrderedByIndex(): array
    {
        return $this->getEntityManager()->createQuery('SELECT rl FROM App\Entity\ReligionLevel rl ORDER BY rl.index ASC')->getResult();
    }
}
