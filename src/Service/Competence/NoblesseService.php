<?php

declare(strict_types=1);

namespace App\Service\Competence;

use App\Service\CompetenceService;

class NoblesseService extends CompetenceService
{
    protected bool $hasBonus = true;

    protected function give(): void
    {
        $level = $this->getCompetence()->getLevel();

        $valuesMap = [
            1 => 2,
            2 => 3,
            3 => 2,
            4 => 5,
            5 => 6,
        ];

        $value = $valuesMap[$level?->getId()] ?? 0;

        if ($level && $value > 0) {
            $this->addRenomme($value, \sprintf('Compétence %s niveau %s', $this->getCompetence()->getCompetenceFamily()?->getLabel(), $level->getLabel()));
        }
    }

    protected function remove(): void
    {
        $level = $this->getCompetence()->getLevel();

        $valuesMap = [
            1 => 2,
            2 => 3,
            3 => 2,
            4 => 5,
            5 => 6,
        ];

        $value = $valuesMap[$level?->getId()] ?? 0;

        if ($level && $value > 0) {
            $this->removeRenomme($value, \sprintf('[Retrait] %s niveau %s', $this->getCompetence()->getCompetenceFamily()?->getLabel(), $level->getLabel()));
        }
    }
}
