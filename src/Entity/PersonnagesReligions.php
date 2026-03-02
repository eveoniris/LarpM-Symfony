<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping\Entity;

#[Entity]
class PersonnagesReligions extends BasePersonnagesReligions
{
    /** @return array<string, mixed> */
    public function toLog(): array
    {
        return [
            'personnage_id' => $this->personnage->getId(),
            'religion_id' => $this->getReligion()?->getId(),
        ];
    }
}
