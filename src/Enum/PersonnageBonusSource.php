<?php

declare(strict_types=1);

namespace App\Enum;

enum PersonnageBonusSource: string
{
    use EnumTraits;

    case ESPECE = 'ESPECE';
    case ORIGINE = 'ORIGINE';
}
