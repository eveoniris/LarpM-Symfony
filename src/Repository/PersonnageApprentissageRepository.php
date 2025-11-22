<?php

namespace App\Repository;

use App\Entity\Personnage;

class PersonnageApprentissageRepository extends BaseRepository
{
    public function hasApprentissage(Personnage $personnage, ?int $fromDate = null, ?int $toDate = null): bool
    {
        // handle bad param
        if ($toDate && $fromDate && $fromDate > $toDate) {
            $tmpDate = $fromDate;
            $fromDate = $toDate;
            $toDate = $tmpDate;
        }

        $dql = <<<DQL
                SELECT COUNT(pa) AS exists
                FROM App\Entity\PersonnageApprentissage pa
                WHERE pa.personnage = :pid AND pa.deleted_at IS NULL
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
