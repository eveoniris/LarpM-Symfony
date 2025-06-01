<?php

namespace App\Entity;

use Doctrine\ORM\Mapping\Entity;

#[Entity]
class PersonnagesReligions extends BasePersonnagesReligions
{
    public function toLog(): array
    {
        return [
            'personnage_id' => $this->personnage->getId(),
            'religion_id' => $this->getReligion()?->getId(),
        ];
    }
}
