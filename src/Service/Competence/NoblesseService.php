<?php

namespace App\Service\Competence;

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
                $value,
                sprintf(
                    'Compétence %s niveau %s',
                    $this->getCompetence()->getCompetenceFamily()?->getLabel(),
                    $level->getLabel()
                )
            );
        }
    }

    protected function remove(): void
    {
        $level = $this->getCompetence()->getLevel();
        $value = $level?->getId() + 1;

        if ($level && $value > 1 && $value < 7) {
            $this->removeRenomme(
                $value,
                sprintf(
                    '[Retrait] %s niveau %s',
                    $this->getCompetence()->getCompetenceFamily()?->getLabel(),
                    $level->getLabel()
                )
            );
        }
    }
}
