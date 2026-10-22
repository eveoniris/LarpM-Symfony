<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Deux notions distinctes : les étiquettes d'armes, produites en masse et données aux joueurs (le numéro de gauche est un
 * numéro de stock ou une feinte), et les étiquettes d'objets importants (le numéro de gauche désigne un objet connu).
 */
enum EtiquetteNature: string
{
    use EnumTraits;

    case ARME = 'arme';
    case OBJET = 'objet';

    public function label(): string
    {
        return match ($this) {
            self::ARME => 'Étiquette d\'arme',
            self::OBJET => 'Étiquette d\'objet',
        };
    }
}
