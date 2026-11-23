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

    /** Verrouille strictement les groupes du GN (admins seuls) : étape technique, sans effet de compétence, date propre. */
    case VERROUILLAGE_STRICT = 'verrouillage_strict';

    /** Déverrouille tous les groupes du GN (souple et strict), à placer après l'XP de participation et le vieillissement. */
    case DEVERROUILLAGE = 'deverrouillage';

    public function getLabel(): string
    {
        return match ($this) {
            self::INFO => 'Information (aucune action)',
            self::XP_PARTICIPATION => 'XP de participation',
            self::VERROUILLAGE => 'Verrouillage des groupes + effets de compétence (renommée, littérature, sanctuaires)',
            self::VIEILLISSEMENT => 'Vieillissement de tous les personnages',
            self::VERROUILLAGE_STRICT => 'Verrouillage strict des groupes (admins seuls)',
            self::DEVERROUILLAGE => 'Déverrouillage des groupes',
        };
    }

    /**
     * Étape qui traite un gros volume (tous les participants ou tous les personnages) : le cron n'en lance qu'une par
     * exécution, et les étapes suivantes attendent.
     */
    public function estLourde(): bool
    {
        return match ($this) {
            self::XP_PARTICIPATION, self::VERROUILLAGE, self::VIEILLISSEMENT => true,
            self::INFO, self::VERROUILLAGE_STRICT, self::DEVERROUILLAGE => false,
        };
    }

    /** Étape technique réservée aux admins : visibilité forcée à « technique ». */
    public function estTechnique(): bool
    {
        return self::VERROUILLAGE_STRICT === $this || self::DEVERROUILLAGE === $this;
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
            self::XP_PARTICIPATION, self::VERROUILLAGE, self::VIEILLISSEMENT, self::VERROUILLAGE_STRICT, self::DEVERROUILLAGE => true,
            self::INFO => false,
        };
    }
}
