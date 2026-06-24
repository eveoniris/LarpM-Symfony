<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping\Entity;

#[Entity]
class PersonnageLangues extends BasePersonnageLangues
{
    /** @return array<string, mixed> */
    public function toLog(): array
    {
        return [
            'personnage_id' => $this->personnage->getId(),
            'source' => $this->getSource(),
            'trigger_tag' => $this->getTriggerTag(),
            'langue_id' => $this->getLangue()?->getId(),
        ];
    }
}
