<?php

namespace App\Service\Competence;

use App\Entity\Level;
use App\Entity\PersonnageTrigger;
use App\Service\CompetenceService;

class ArtisantService extends CompetenceService
{
    protected bool $hasBonus = true;

    public function give(): void
    {
        $this->applyRules($this->getRules());
    }

    public function remove(): void
    {
        $this->removeRules($this->getRules());
    }

    public function getRules(): array
    {
        return [
            // le personnage doit choisir 1 technologie
            Level::NIVEAU_3 => [PersonnageTrigger::TAG_TECHNOLOGIE => 1],
            Level::NIVEAU_4 => [PersonnageTrigger::TAG_TECHNOLOGIE => 1],
        ];
    }
}
