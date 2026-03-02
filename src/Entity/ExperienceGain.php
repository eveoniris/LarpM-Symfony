<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping\Entity;

#[Entity]
class ExperienceGain extends BaseExperienceGain
{
    /** @return array<string, mixed> */
    public function toLog(): array
    {
        return [
            'personnage_id' => $this->personnage->getId(),
            'xp' => $this->xp_gain,
            'explanation' => $this->explanation,
        ];
    }
}
