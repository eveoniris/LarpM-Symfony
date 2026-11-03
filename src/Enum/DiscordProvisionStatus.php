<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Résultat du provisionnement d'un groupe transverse sur une guilde Discord.
 */
enum DiscordProvisionStatus: string
{
    case PLANIFIE = 'planifie';
    case CREE = 'cree';
    case ERREUR = 'erreur';

    public function label(): string
    {
        return match ($this) {
            self::PLANIFIE => 'À créer',
            self::CREE => 'Créé',
            self::ERREUR => 'Erreur',
        };
    }
}
