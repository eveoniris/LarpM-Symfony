<?php

namespace App\Enum;

enum BonusType: string
{
    use EnumTraits;

    case RESSOURCE = 'RESSOURCE';
    case COMPETENCE = 'COMPETENCE';
    case LANGUE = 'LANGUE';
    case RENOMME = 'RENOMME';
    case INGREDIENT = 'INGREDIENT';
    case XP = 'XP';
    case MISSION_COMMERCIAL = 'MISSION_COMMERCIAL';
}
