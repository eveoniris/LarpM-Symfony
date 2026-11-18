<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Mécanisme par lequel une connexion a été tentée ou réalisée.
 */
enum LoginMethod: string
{
    /** Formulaire de connexion web. */
    case WEB = 'web';

    /** Connexion par cookie « se souvenir de moi ». */
    case REMEMBER_ME = 'remember_me';

    /** Connexion à l'API (JWT). */
    case API_JWT = 'api_jwt';

    /** Impersonation par un super admin (switch_user). */
    case SWITCH_USER = 'switch_user';
}
