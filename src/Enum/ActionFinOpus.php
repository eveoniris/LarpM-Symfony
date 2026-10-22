<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Les six actions de fin d'Opus de la fiche de retour joueur : chacune se fait une seule fois par GN, en consommant des
 * ressources jointes à l'enveloppe du joueur.
 */
enum ActionFinOpus: string
{
    use EnumTraits;

    case FRUITS_LEGUMES = 'fruits_legumes';
    case SOIE = 'soie';
    case GEMME = 'gemme';
    case SEL = 'sel';
    case FOURRURE = 'fourrure';
    case IVOIRE = 'ivoire';

    public function label(): string
    {
        return match ($this) {
            self::FRUITS_LEGUMES => 'Fruits et légumes : prolonger l\'âge maximal',
            self::SOIE => 'Soie : augmenter la renommée',
            self::GEMME => 'Gemme : conserver des pièces d\'or',
            self::SEL => 'Sel : conserver des ingrédients',
            self::FOURRURE => 'Fourrure : conserver des ressources',
            self::IVOIRE => 'Ivoire : conserver des potions',
        };
    }

    /** Nom de la ressource consommée. */
    public function ressource(): string
    {
        return match ($this) {
            self::FRUITS_LEGUMES => 'Fruits et légumes',
            self::SOIE => 'Soie',
            self::GEMME => 'Gemme',
            self::SEL => 'Sel',
            self::FOURRURE => 'Fourrure',
            self::IVOIRE => 'Ivoire',
        };
    }

    /** Nombre de ressources à joindre à l'enveloppe pour faire l'action. */
    public function quantiteRessource(): int
    {
        return match ($this) {
            self::FRUITS_LEGUMES => 5,
            self::SOIE => 3,
            default => 1,
        };
    }

    /** Nombre maximal d'éléments conservés (pièces, ingrédients, ressources ou potions), null si l'action n'en conserve pas. */
    public function plafond(): ?int
    {
        return match ($this) {
            self::GEMME, self::FOURRURE, self::IVOIRE => 3,
            self::SEL => 6,
            default => null,
        };
    }

    public function effet(): string
    {
        return match ($this) {
            self::FRUITS_LEGUMES => '+5 ans d\'âge maximal (deux fois maximum durant la vie du personnage, sans effet au-delà)',
            self::SOIE => '+1 point de renommée',
            self::GEMME => 'conserve jusqu\'à 3 pièces d\'or',
            self::SEL => 'conserve jusqu\'à 6 ingrédients au prochain GN',
            self::FOURRURE => 'conserve jusqu\'à 3 ressources jusqu\'au prochain GN',
            self::IVOIRE => 'conserve jusqu\'à 3 potions jusqu\'au prochain GN',
        };
    }
}
