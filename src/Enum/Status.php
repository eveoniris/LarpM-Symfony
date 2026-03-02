<?php

declare(strict_types=1);

namespace App\Enum;

enum Status: string
{
    use EnumTraits;

    case ACTIVE = 'ACTIVE';
    case DELETED = 'DELETED';
    case CLOSED = 'CLOSED';
    case EXPIRED = 'EXPIRED';
    case PENDING = 'PENDING';
    case DONE = 'DONE';
    case ERROR = 'ERROR';
    case PROCESSING = 'PROCESSING';
    case WAITING = 'WAITING';
}
