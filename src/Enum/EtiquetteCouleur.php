<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Bleue : objet de l'association, l'étiquette est indissociable de l'objet (vol : on prend l'objet et l'étiquette).
 * Orange : objet d'un joueur, il reste au joueur (vol : on ne prend que l'étiquette).
 */
enum EtiquetteCouleur: string
{
    use EnumTraits;

    case ORANGE = 'orange';
    case BLEUE = 'bleue';

    public function label(): string
    {
        return match ($this) {
            self::ORANGE => 'Orange (objet du joueur)',
            self::BLEUE => 'Bleue (objet de l\'association)',
        };
    }
}
