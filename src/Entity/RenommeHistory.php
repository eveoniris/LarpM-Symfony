<?php

namespace App\Entity;

use App\Repository\RenommeHistoryRepository;
use Doctrine\ORM\Mapping\Entity;

#[Entity(repositoryClass: RenommeHistoryRepository::class)]
class RenommeHistory extends BaseRenommeHistory
{
    public function toLog(): array
    {
        return [
            'explication' => $this->getExplication(),
            'renomme' => $this->getRenomme(),
            'personnage_id' => $this->getPersonnage()->getId(),
        ];
    }
}
