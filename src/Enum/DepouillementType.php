<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Nature de l'enveloppe dépouillée. L'enveloppe de groupe est traitée en dernier : elle récupère le reliquat des enveloppes joueurs.
 */
enum DepouillementType: string
{
    use EnumTraits;

    case JOUEUR = 'joueur';
    case GROUPE = 'groupe';

    public function label(): string
    {
        return match ($this) {
            self::JOUEUR => 'Enveloppe de joueur',
            self::GROUPE => 'Enveloppe de groupe',
        };
    }
}
