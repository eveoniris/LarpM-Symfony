<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\FicheRetourGroupe;
use App\Entity\FicheRetourGroupeHistory;

class FicheRetourGroupeHistoryRepository extends BaseRepository
{
    /** @return FicheRetourGroupeHistory[] */
    public function findByFiche(FicheRetourGroupe $fiche): array
    {
        return $this->getEntityManager()->getRepository(FicheRetourGroupeHistory::class)->findBy(['ficheRetourGroupe' => $fiche], ['action_date' => 'DESC']);
    }

    public function findInitialState(FicheRetourGroupe $fiche): ?FicheRetourGroupeHistory
    {
        return $this
            ->getEntityManager()
            ->getRepository(FicheRetourGroupeHistory::class)
            ->findOneBy(['ficheRetourGroupe' => $fiche, 'action_type' => [FicheRetourGroupeHistory::ACTION_CREATE, FicheRetourGroupeHistory::ACTION_IMPORT]], ['action_date' => 'ASC']);
    }
}
