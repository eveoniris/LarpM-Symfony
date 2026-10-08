<?php

declare(strict_types=1);

namespace App\Enum;

/** Niveau de confiance du rapprochement entre un participant HelloAsso et un compte LarpManager. */
enum SyncConfiance: string
{
    use EnumTraits;

    /** ID LarpManager saisi par le joueur : correspondance certaine. */
    case SUR = 'sur';

    /** Adresse e-mail unique : correspondance probable. */
    case PROBABLE = 'probable';

    /** Plusieurs comptes possibles : à traiter à la main. */
    case AMBIGU = 'ambigu';

    /** Aucun compte trouvé. */
    case AUCUN = 'aucun';

    public function getLabel(): string
    {
        return match ($this) {
            self::SUR => 'Sûr (ID LarpManager)',
            self::PROBABLE => 'Probable (e-mail)',
            self::AMBIGU => 'Ambigu',
            self::AUCUN => 'Aucun compte',
        };
    }
}
