<?php

namespace App\Entity;

use Doctrine\ORM\Mapping\Entity;

#[Entity]
class PersonnageTrigger extends BasePersonnageTrigger
{
    public function isDone(): bool
    {
        return (bool) $this->done;
    }

    public function toLog(): array
    {
        return [
            'personnage_id' => $this->personnage->getId(),
            'TAG' => $this->getTag()?->value,
        ];
    }
}
