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
    case A_RATTACHER = 'a_rattacher';
    case RATTACHE = 'rattache';
    case ERREUR = 'erreur';

    public function label(): string
    {
        return match ($this) {
            self::PLANIFIE => 'À créer',
            self::CREE => 'Créé',
            self::A_RATTACHER => 'À rattacher (existe déjà sur Discord)',
            self::RATTACHE => 'Rattaché',
            self::ERREUR => 'Erreur',
        };
    }
}
