<?php

declare(strict_types=1);

namespace App\Enum;

enum TerritoireStatut: string
{
    use EnumTraits;

    case STABLE = 'stable';
    case ATTAQUE = 'attaque';
    case INSTABLE = 'instable';
}
