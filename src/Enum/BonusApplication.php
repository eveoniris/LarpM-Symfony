<?php

namespace App\Enum;

enum BonusApplication: string
{
    use EnumTraits;

    case ENVELOPPE_PJ = 'ENVELOPPE_PJ';
    case ENVELOPPE_GROUPE = 'ENVELOPPE_GROUPE';
    case FICHE_PJ = 'FICHE_PJ';
    case FICHE_GROUPE = 'FICHE_GROUPE';
    case LARPMANAGER = 'LARPMANAGER';
}
