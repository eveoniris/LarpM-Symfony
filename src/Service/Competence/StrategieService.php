<?php

namespace App\Service\Competence;

use App\Enum\LevelType;
use App\Service\CompetenceService;

class StrategieService extends CompetenceService
{
    protected bool $hasBonus = true;

    protected function give(): void
    {
        $level = $this->getCompetence()->getLevel();

        if ($level && $level->getId() === LevelType::GRAND_MASTER->getId()) {
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
