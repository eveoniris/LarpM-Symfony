<?php

namespace App\Service\Competence;

use App\Entity\Level;
use App\Entity\PersonnageTrigger;
use App\Entity\RenommeHistory;
use App\Service\CompetenceService;

class NoblesseService extends CompetenceService
{
    protected bool $hasBonus = true;

    protected function give(): void
    {
        $level = $this->getCompetence()->getLevel();
        $value = $level?->getId() + 1;

        if ($level && $value > 1 && $value < 7) {
            $this->addRenomme(
                5,
                sprintf(
                    'Compétence %s niveau %s',
                    $this->getCompetence()->getCompetenceFamily()?->getLabel(),
                    $level->getLabel()
                )
            );
        }
    }
}
