<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Nature d'une ligne reportée dans l'enveloppe du personnage pour l'opus suivant.
 */
enum EnveloppeLigneNature: string
{
    use EnumTraits;

    /** Étiquette laissée dans l'enveloppe au retour de jeu, à réinsérer dans l'enveloppe de l'opus suivant. */
    case ETIQUETTE_A_REINSERER = 'etiquette_a_reinserer';
    /** Objet à remettre au PC joueur (avec son étiquette bleue, ou objet trouvé avec une étiquette orange). */
    case OBJET_AU_PC = 'objet_au_pc';

    public function label(): string
    {
        return match ($this) {
            self::ETIQUETTE_A_REINSERER => 'Étiquette à réinsérer dans l\'enveloppe',
            self::OBJET_AU_PC => 'Objet à remettre au PC joueur',
        };
    }
}
