<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Où va l'étiquette après le dépouillement.
 */
enum EtiquetteDestination: string
{
    use EnumTraits;

    /** Étiquette générique : remise au stock (une neuve sera donnée pour la prochaine enveloppe). */
    case STOCK = 'stock';
    /** Étiquette spéciale, exceptionnelle ou chef-d'oeuvre : laissée dans l'enveloppe, à réinsérer pour l'opus suivant. */
    case ENVELOPPE = 'enveloppe';
    /** Étiquette bleue : reste avec son objet, remis au PC joueur. */
    case AVEC_OBJET_AU_PC = 'avec_objet_au_pc';
    /** Étiquette bleue sans son objet : consignée comme étant au joueur, à rapporter au PC joueur. */
    case AU_JOUEUR = 'au_joueur';

    public function label(): string
    {
        return match ($this) {
            self::STOCK => 'Remise au stock',
            self::ENVELOPPE => 'Laissée dans l\'enveloppe (à réinsérer pour l\'opus suivant)',
            self::AVEC_OBJET_AU_PC => 'Avec son objet, remis au PC joueur',
            self::AU_JOUEUR => 'Au joueur, à rapporter au PC joueur',
        };
    }
}
