<?php

namespace App\Entity;

use Doctrine\ORM\Mapping\Entity;

#[Entity]
class PersonnageLangues extends BasePersonnageLangues
{
    public function toLog(): array
    {
        return [
            'personnage_id' => $this->personnage->getId(),
            'source' => $this->getSource(),
            'langue_id' => $this->getLangue()->getId(),
        ];
    }
}
