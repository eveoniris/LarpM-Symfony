<?php

namespace App\Enum;

enum LogActionType: string
{
    use EnumTraits;

    case ADD_COMPETENCE = 'add_competence';
    case ADD_CONNAISSANCE = 'add_connaissance';
    case REMOVE_CONNAISSANCE = 'remove_connaissance';
    case AGING_CHARACTERS = 'AgingCharacters';
    case ENTITY_UPDATE = 'entity_update';
    case ENTITY_DELETE = 'entity_delete';
    case ENTITY_ADD = 'entity_add';
    case XP_ADD = 'xp_add';
    case XP_USE = 'xp_use';
    case ADD_DOMAINE_MAGIE = 'add_domaine_magie';
    case ADD_SORT = 'add_sort';
    case REMVOVE_SORT = 'remove_sort';
    case ADD_LANGUE = 'add_langue';
    case ADD_ORIGINE = 'add_origine';
    case ADD_POTION = 'add_potion';
    case REMOVE_POTION = 'remove_potion';
    case ADD_POTION_DEPART = 'add_potion_depart';
    case ADD_RELIGION = 'add_religion';
    case ADD_RENOMME = 'add_renomme';
    case DELETE_POTION_DEPART = 'delete_potion_depart';
    case ENTITY = 'entity';
    case OTHER = 'autre';
}
