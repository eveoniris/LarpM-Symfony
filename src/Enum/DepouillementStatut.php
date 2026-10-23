<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Avancement du dépouillement d'une enveloppe de retour de jeu.
 *
 * en_depouillement -> a_valider -> valide -> (en_attente_import) -> termine.
 * Avant la validation, les données restent un brouillon de suivi (rien n'est appliqué aux joueurs ni aux groupes).
 */
enum DepouillementStatut: string
{
    use EnumTraits;

    /** Le dépouilleur saisit le contenu de l'enveloppe. */
    case EN_DEPOUILLEMENT = 'en_depouillement';
    /** Saisie terminée, en attente de validation par un scénariste, wargame ou admin. */
    case A_VALIDER = 'a_valider';
    /** Validé, le dispatch peut être appliqué. */
    case VALIDE = 'valide';
    /** Validé, mais la session de jeu suivante n'est pas encore ouverte : import différé. */
    case EN_ATTENTE_IMPORT = 'en_attente_import';
    /** Dispatch appliqué, les données deviennent un historique. */
    case TERMINE = 'termine';

    public function label(): string
    {
        return match ($this) {
            self::EN_DEPOUILLEMENT => 'En dépouillement',
            self::A_VALIDER => 'À valider',
            self::VALIDE => 'Validé',
            self::EN_ATTENTE_IMPORT => 'En attente d\'import',
            self::TERMINE => 'Terminé',
        };
    }

    /** La saisie est encore modifiable par le dépouilleur. */
    public function isEditable(): bool
    {
        return self::EN_DEPOUILLEMENT === $this;
    }
}
