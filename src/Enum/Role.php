<?php

namespace App\Enum;

enum Role: string
{
    use EnumTraits;

    case ADMIN = 'ROLE_ADMIN';
    case CARTOGRAPHE = 'ROLE_CARTOGRAPHE';
    case DEV = 'ROLE_DEV';
    case GESTION = 'ROLE_GESTION';
    case MODERATEUR = 'ROLE_MODERATEUR';
    case ORGA = 'ROLE_ORGA';
    case REDACTEUR = 'ROLE_REDACTEUR';
    case REGLE = 'ROLE_REGLE';
    case SCENARISTE = 'ROLE_SCENARISTE';
    case STOCK = 'ROLE_STOCK';
    case USER = 'ROLE_USER';

}
