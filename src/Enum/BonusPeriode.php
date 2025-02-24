<?php

namespace App\Enum;

enum BonusPeriode: string
{
    use EnumTraits;

    case GN = 'GN';
    case UNIQUE = 'UNIQUE';
    case CONSTANT = 'CONSTANT';
    case NATIVE = 'NATIVE';
}
