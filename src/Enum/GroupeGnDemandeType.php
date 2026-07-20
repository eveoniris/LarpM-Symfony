<?php

declare(strict_types=1);

namespace App\Enum;

enum GroupeGnDemandeType: string
{
    use EnumTraits;

    /** Candidature émise par un joueur qui souhaite rejoindre un groupe. */
    case CANDIDATURE = 'CANDIDATURE';

    /** Invitation émise par un chef de groupe vers un joueur. */
    case INVITATION = 'INVITATION';
}
