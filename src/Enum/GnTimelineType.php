<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Nature d'une étape de la chronologie d'un GN : simple jalon d'information ou action de jeu exécutable.
 */
enum GnTimelineType: string
{
    /** Jalon d'information, sans action. */
    case INFO = 'info';

    /** Donne l'XP de participation aux personnages des participants du GN. */
    case XP_PARTICIPATION = 'xp_participation';

    /**
     * Verrouille les groupes du GN et applique les effets de fin d'édition, même chaîne que « Verrouiller » sur la fiche GN :
     * personnages et langues par défaut, renommée des nobles experts, trigger de littérature, effet des sanctuaires.
     * Ces gains de compétence sont volontairement inclus ici ; à séparer en étapes distinctes si les règles changent.
     */
    case VERROUILLAGE = 'verrouillage';

    /** Vieillit tous les personnages (global, pas lié au GN) ; années et âge de mort réglables sur l'étape. */
    case VIEILLISSEMENT = 'vieillissement';

    public function getLabel(): string
    {
        return match ($this) {
            self::INFO => 'Information (aucune action)',
            self::XP_PARTICIPATION => 'XP de participation',
            self::VERROUILLAGE => 'Verrouillage des groupes + effets de compétence (renommée, littérature, sanctuaires)',
            self::VIEILLISSEMENT => 'Vieillissement de tous les personnages',
        };
    }

    public function estAction(): bool
    {
        return self::INFO !== $this;
    }

    /**
     * Le cron ne lance que les actions idempotentes. Le vieillissement l'est aussi (garde-fou sur la date de jeu dans
     * PersonnageService::vieillirTous), mais il est global et définitif : à n'activer qu'en connaissance de cause.
     */
    public function autoAutorise(): bool
    {
        return match ($this) {
            self::XP_PARTICIPATION, self::VERROUILLAGE, self::VIEILLISSEMENT => true,
            self::INFO => false,
        };
    }
}
