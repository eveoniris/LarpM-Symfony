<?php

namespace App\Entity;

use Doctrine\ORM\Mapping\Entity;

#[Entity]
class ExperienceGain extends BaseExperienceGain
{
    public function toLog(): array
    {
        return [
            'personnage_id' => $this->personnage->getId(),
            'xp' => $this->xp_gain,
            'explanation' => $this->explanation,
        ];
    }
}
