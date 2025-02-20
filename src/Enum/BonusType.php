<?php

namespace App\Enum;

enum BonusType: string
{
    use EnumTraits;

    case RESSOURCE = 'RESSOURCE';
    case COMPETENCE = 'COMPETENCE';
    case LANGUE = 'LANGUE';
    case RENOMME = 'RENOMME';
    case HEROISME = 'HEROISME';
    case PUGILAT = 'PUGILAT';
    case RICHESSE = 'RICHESSE';
    case INGREDIENT = 'INGREDIENT';
    case MATERIEL = 'MATERIEL';
    case ITEM = 'ITEM';
    case XP = 'XP';
    case MISSION_COMMERCIAL = 'MISSION_COMMERCIAL';
}
