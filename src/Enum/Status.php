<?php

namespace App\Enum;

enum Status: string
{
    use EnumTraits;

    case ACTIVE = 'ACTIVE';
    case DELETED = 'DELETED';
    case CLOSED = 'CLOSED';
    case PENDING = 'PENDING';
    case DONE = 'DONE';
    case ERROR = 'ERROR';
}
