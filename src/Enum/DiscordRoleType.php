<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Ce qu'un rôle Discord représente pour LarpManager dans une guilde.
 */
enum DiscordRoleType: string
{
    use EnumTraits;

    /** Tout compte lié (dès ROLE_USER). */
    case MEMBRE = 'membre';
    /** Rôle unique pour ROLE_ORGA, ROLE_SCENARISTE et ROLE_ADMIN. */
    case ORGA = 'orga';
    /** Rôle d'un groupe transverse. */
    case GROUPE_SECONDAIRE = 'groupe_secondaire';

    public function label(): string
    {
        return match ($this) {
            self::MEMBRE => 'Membre',
            self::ORGA => 'Orga / scénariste',
            self::GROUPE_SECONDAIRE => 'Groupe transverse',
        };
    }
}
