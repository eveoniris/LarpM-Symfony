<?php

namespace App\Service\Competence;

use App\Entity\Level;
use App\Entity\PersonnageTrigger;
use App\Enum\TriggerType;
use App\Service\CompetenceService;

class LitteratureService extends CompetenceService
{
    protected bool $hasBonus = true;

    public function give(): void
    {
        // Todo filter if personnage know all of them or will reach more than exists
        $this->applyRules($this->getRules());
    }

    public function getRules(): array
    {
        return [
            // 2 langues communes supplémentaires de son choix
            Level::NIVEAU_1 => [TriggerType::LANGUE_COURANTE->value => 2],
            Level::NIVEAU_2 => [
                //  Sait parler, lire et écrire trois autres langues vivantes (courante ou commune) de son choix.
                TriggerType::LANGUE_COURANTE->value => 3,
                // il obtient aussi la possibilité de choisir un sort de niveau 1
                TriggerType::SORT_APPRENTI->value => 1,
                // il obtient aussi la possibilité de choisir une potion de niveau 1
                TriggerType::ALCHIMIE_APPRENTI->value => 1,
            ],
            Level::NIVEAU_3 => [
                // Sait parler, lire et écrire un langage ancien ainsi que trois autres langues vivantes
                // (courante ou commune) de son choix ainsi qu'une langue ancienne
                TriggerType::LANGUE_COURANTE->value => 3,
                TriggerType::LANGUE_ANCIENNE->value => 1,
                // il obtient aussi la possibilité de choisir un sort et une potion de niveau 2
                TriggerType::SORT_INITIE->value => 1,
                // il obtient aussi la possibilité de choisir une potion de niveau 2
                TriggerType::ALCHIMIE_INITIE->value => 1,
            ],
            Level::NIVEAU_4 => [
                // Sait parler, lire et écrire un langage ancien ainsi que trois autres langues vivantes
                // (courante ou commune) de son choix ainsi qu'une langue ancienne
                TriggerType::LANGUE_COURANTE->value => 3,
                TriggerType::LANGUE_ANCIENNE->value => 1,
                // il obtient aussi la possibilité de choisir un sort et une potion de niveau 2
                TriggerType::SORT_EXPERT->value => 1,
                // il obtient aussi la possibilité de choisir une potion de niveau 2
                TriggerType::ALCHIMIE_EXPERT->value => 1,
            ],
        ];
    }

    public function remove(): void
    {
        $this->removeRules($this->getRules());
    }
}
