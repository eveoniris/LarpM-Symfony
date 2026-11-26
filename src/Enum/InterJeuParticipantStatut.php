<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Statut d'un personnage dans la liste des participants d'un inter-jeu.
 *
 * Seuls les inscrits comptent dans les statistiques, les impressions et la chronologie.
 */
enum InterJeuParticipantStatut: string
{
    use EnumTraits;

    case EN_ATTENTE = 'en_attente';
    case INSCRIT = 'inscrit';
    case DESISTE = 'desiste';

    public function getLabel(): string
    {
        return self::getLabels()[$this->value] ?? $this->value;
    }

    /** @return array<string, string> */
    public static function getLabels(): array
    {
        return [
            self::EN_ATTENTE->value => 'En attente',
            self::INSCRIT->value => 'Inscrit',
            self::DESISTE->value => 'Désisté',
        ];
    }

    /**
     * Lecture tolérante d'un statut saisi dans un import (valeur ou libellé, casse et accents ignorés).
     */
    public static function fromSaisie(?string $saisie): ?self
    {
        $normalise = mb_strtolower(trim((string) $saisie));
        if ('' === $normalise) {
            return null;
        }

        $normalise = strtr($normalise, ['é' => 'e', 'è' => 'e', 'ê' => 'e', ' ' => '_', '-' => '_']);

        return match ($normalise) {
            'en_attente', 'attente' => self::EN_ATTENTE,
            'inscrit', 'inscrite' => self::INSCRIT,
            'desiste', 'desistee', 'desistement' => self::DESISTE,
            default => null,
        };
    }

    public function getBadgeClass(): string
    {
        return match ($this) {
            self::EN_ATTENTE => 'text-bg-warning',
            self::INSCRIT => 'text-bg-success',
            self::DESISTE => 'text-bg-secondary',
        };
    }
}
