<?php

declare(strict_types=1);

namespace App\Enum;

enum ChronologyType: string
{
    use EnumTraits;

    case FRUITS_AND_VEGETABLES = 'Consommation de Fruits & Légumes';
    case DIED_OF_OLD_AGE = 'Mort de vieillesse';

    public function isFruitsAndVegetables(): bool
    {
        return $this->value === self::FRUITS_AND_VEGETABLES->value;
    }

    public function isDiedOfOldAge(): bool
    {
        return $this->value === self::DIED_OF_OLD_AGE->value;
    }
}
