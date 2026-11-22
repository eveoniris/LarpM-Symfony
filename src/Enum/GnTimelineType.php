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

    /** Vieillit tous les personnages (global, pas lié au GN). Manuel uniquement. */
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
     * Le cron ne peut lancer que les actions peu risquées et idempotentes ; le vieillissement est global et reste manuel.
     */
    public function autoAutorise(): bool
    {
        return match ($this) {
            self::XP_PARTICIPATION, self::VERROUILLAGE => true,
            self::INFO, self::VIEILLISSEMENT => false,
        };
    }
}
