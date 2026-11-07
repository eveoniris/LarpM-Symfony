<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Statut d'une règle du rulebook (légende reprise de la spec de gameplay).
 *
 * Seuls les statuts "publics" sont visibles des scénaristes. Les autres (bugué, à confirmer, à faire) restent réservés
 * à l'équipe règle.
 */
enum RulebookStatut: string
{
    use EnumTraits;

    /** Règle codée et conforme. */
    case VALIDE = 'valide';
    /** Le code ne respecte pas la règle, un correctif est connu. */
    case BUGUE = 'bugue';
    /** Bug corrigé. */
    case FIXE = 'fixe';
    /** Règle décidée, pas encore codée. */
    case TODO = 'todo';
    /** Valeur codée en dur dans le code (non paramétrable). */
    case HARDCODE = 'hardcode';
    /** Règle incertaine, à trancher par l'équipe règle. */
    case A_CONFIRMER = 'a_confirmer';

    public function label(): string
    {
        return match ($this) {
            self::VALIDE => 'Valide',
            self::BUGUE => 'Bugué',
            self::FIXE => 'Corrigé',
            self::TODO => 'À faire',
            self::HARDCODE => 'Codé en dur',
            self::A_CONFIRMER => 'À confirmer',
        };
    }

    /** Un scénariste peut-il voir une règle dans ce statut (en plus de sa publication) ? */
    public function isPublic(): bool
    {
        return match ($this) {
            self::VALIDE, self::FIXE, self::HARDCODE => true,
            self::BUGUE, self::TODO, self::A_CONFIRMER => false,
        };
    }
}
