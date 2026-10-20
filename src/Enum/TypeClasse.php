<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Type d'une classe de personnage (guide de style, section 4c).
 *
 * Le type n'est pas stocké en base : il est déduit du libellé de la classe.
 */
enum TypeClasse: string
{
    case COMBAT = 'combat';
    case SOCIAL = 'social';
    case SAVOIR = 'savoir';
    case AUTRE = 'autre';

    private const array LABELS = [
        'combat' => ['bourreau', 'chevalier', 'ranger', 'sauvage', 'soldat', 'tacticien'],
        'social' => ['artiste', 'brigand', 'espion', 'intrigant', 'négociant', 'noble de robe'],
        'savoir' => ['conseiller', 'explorateur', 'forgeron', 'prêtre', 'rebouteux'],
    ];

    public static function fromLabel(string $label): self
    {
        $label = mb_strtolower(trim($label));

        foreach (self::LABELS as $type => $labels) {
            if (\in_array($label, $labels, strict: true)) {
                return self::from($type);
            }
        }

        return self::AUTRE;
    }

    public function label(): string
    {
        return match ($this) {
            self::COMBAT => 'Combat',
            self::SOCIAL => 'Social',
            self::SAVOIR => 'Savoir',
            self::AUTRE => 'Autre',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::COMBAT => 'fa-gavel',
            self::SOCIAL => 'fa-comments',
            self::SAVOIR => 'fa-scroll',
            self::AUTRE => 'fa-circle',
        };
    }
}
