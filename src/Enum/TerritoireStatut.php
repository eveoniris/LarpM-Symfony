<?php

namespace App\Enum;

enum TerritoireStatut: string
{
    use EnumTraits;

    case STABLE = 'stable';
    case ATTAQUE = 'attaque';
    case INSTABLE = 'instable';

}
