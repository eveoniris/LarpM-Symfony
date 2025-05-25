<?php

namespace App\Service\Competence;

use App\Entity\Level;
use App\Entity\PersonnageTrigger;
use App\Enum\TriggerType;
use App\Service\CompetenceService;

class AlchimieService extends CompetenceService
{
    protected bool $hasBonus = true;

    public function give(): void
    {
        $this->applyRules($this->getRules());
    }

    public function getRules(): array
    {
        // TODO FILTER if personnage know all
        return [
            // le personnage doit choisir 2 potions de niveau apprenti
            Level::NIVEAU_1 => [TriggerType::ALCHIMIE_APPRENTI->value => 2],
            // le personnage doit choisir 1 potion de niveau initie et 1 potion de niveau apprenti
            Level::NIVEAU_2 => [
                TriggerType::ALCHIMIE_APPRENTI->value => 1,
                TriggerType::ALCHIMIE_INITIE->value => 1,
            ],
            // le personnage doit choisir 1 potion de niveau expert
            Level::NIVEAU_3 => [TriggerType::ALCHIMIE_EXPERT->value => 1],
            // le personnage doit choisir 1 potion de niveau maitre
            Level::NIVEAU_4 => [TriggerType::ALCHIMIE_MAITRE->value => 1],
        ];
    }

    public function remove(): void
    {
        $this->removeRules($this->getRules());
    }
}
