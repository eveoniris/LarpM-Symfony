<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Sort d'une action de fin d'Opus après contrôle du contenu réel de l'enveloppe.
 */
enum ActionStatut: string
{
    use EnumTraits;

    case NON_UTILISEE = 'non_utilisee';
    /** Cochée et les ressources exigées sont dans l'enveloppe : l'action a son effet. */
    case ACCEPTEE = 'acceptee';
    /** Cochée mais les ressources exigées manquent : sans effet, ce qui devait être préservé va dans le reliquat. */
    case REFUSEE = 'refusee';

    public function label(): string
    {
        return match ($this) {
            self::NON_UTILISEE => 'Non utilisée',
            self::ACCEPTEE => 'Acceptée',
            self::REFUSEE => 'Refusée',
        };
    }
}
