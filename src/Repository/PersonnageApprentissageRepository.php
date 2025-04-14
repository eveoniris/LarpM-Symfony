<?php

namespace App\Repository;

use App\Entity\Gn;
use App\Entity\Personnage;
use Carbon\Carbon;

class PersonnageApprentissageRepository extends BaseRepository
{
    public function hasApprentissage(Personnage $personnage, ?int $fromDate = null, ?int $toDate = null): bool
    {
        // TODO date_deleted ?

        $dql = <<<DQL
                SELECT COUNT(pa) AS exists
                FROM App\Entity\PersonnageApprentissage pa 
                WHERE pa.personnage = :pid 
                DQL;

        if ($fromDate) {
            $dql .= ' AND pa.date_enseignement >= :fromDate';
        }

        if ($toDate) {
            $dql .= ' AND pa.date_enseignement < :toDate';
        }

        $query = $this->getEntityManager()->createQuery($dql);

        if ($fromDate) {
            $query->setParameter('fromDate', $fromDate);
        }

        if ($toDate) {
            $query->setParameter('toDate', $toDate);
        }

        return (bool) $query->setParameter('pid', $personnage->getId())->getSingleScalarResult();
    }
}
