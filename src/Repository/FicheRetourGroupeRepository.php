<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\FicheRetourGroupe;
use App\Entity\GroupeGn;

class FicheRetourGroupeRepository extends BaseRepository
{
    public function findByGroupeGn(GroupeGn $groupeGn): ?FicheRetourGroupe
    {
        return $this->getEntityManager()
            ->getRepository(FicheRetourGroupe::class)
            ->findOneBy(['groupeGn' => $groupeGn]);
    }
}
