<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * État d'exécution d'une étape de la chronologie.
 */
enum GnTimelineStatut: string
{
    case A_FAIRE = 'a_faire';
    case EN_COURS = 'en_cours';
    case EXECUTE = 'execute';
    case ERREUR = 'erreur';

    public function getLabel(): string
    {
        return match ($this) {
            self::A_FAIRE => 'À faire',
            self::EN_COURS => 'En cours',
            self::EXECUTE => 'Exécutée',
            self::ERREUR => 'Erreur',
        };
    }
}
