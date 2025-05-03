<?php

namespace App\Repository;

class MerveilleRepository extends BaseRepository
{
    public function findAllActiveOrderedByLabel()
    {
        return $this->getEntityManager()
            ->createQuery(
                <<<'DQL'
                SELECT m 
                FROM App\Entity\Merveille m 
                WHERE m.statut = 'active'
                ORDER BY m.nom ASC
                DQL,
            )
            ->getResult();
    }
}
