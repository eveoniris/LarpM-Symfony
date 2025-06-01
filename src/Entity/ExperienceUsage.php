<?php

namespace App\Entity;

use Doctrine\ORM\Mapping\Entity;

#[Entity]
class ExperienceUsage extends BaseExperienceUsage
{
    public function toLog(): array
    {
        return [
            'personnage_id' => $this->personnage->getId(),
            'xp' => $this->xp_use,
            'competence' => $this->getCompetence()->getId(),
        ];
    }
}
