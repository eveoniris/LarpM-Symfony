<?php

namespace App\Enum;

enum TerritoireStatut: string
{
    use EnumTraits;

    case NORMAL = 'normal';
    case ATTAQUE = 'attaque';
    case INSTABLE = 'instable';

}
