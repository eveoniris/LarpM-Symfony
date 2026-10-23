<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Étapes du parcours de dépouillement (formulaire par étapes, mobile et web).
 *
 * L'enveloppe de groupe n'a pas les étapes « Actions de fin d'Opus » et « Conceptions », propres aux joueurs : la position et
 * l'étape suivante ou précédente dépendent donc du type d'enveloppe.
 */
enum DepouillementEtape: string
{
    use EnumTraits;

    case IDENTIFICATION = 'identification';
    case PHOTOS = 'photos';
    case ETIQUETTES = 'etiquettes';
    case CONTENU = 'contenu';
    case ACTIONS = 'actions';
    case CONCEPTIONS = 'conceptions';
    case REMARQUES = 'remarques';
    case RECAPITULATIF = 'recapitulatif';

    public function label(): string
    {
        return match ($this) {
            self::IDENTIFICATION => 'Enveloppe',
            self::PHOTOS => 'Photos',
            self::ETIQUETTES => 'Étiquettes (si présentes)',
            self::CONTENU => 'Contenu',
            self::ACTIONS => 'Actions de fin d\'Opus',
            self::CONCEPTIONS => 'Conceptions',
            self::REMARQUES => 'Remarques',
            self::RECAPITULATIF => 'Récapitulatif',
        };
    }

    /** Étape propre aux enveloppes de joueurs. */
    public function estReserveeAuxJoueurs(): bool
    {
        return \in_array($this, [self::ACTIONS, self::CONCEPTIONS], strict: true);
    }

    /**
     * Étapes du parcours d'un type d'enveloppe, dans l'ordre.
     *
     * @return list<self>
     */
    public static function pourType(DepouillementType $type): array
    {
        return array_values(array_filter(self::cases(), static fn (self $etape): bool => DepouillementType::JOUEUR === $type || !$etape->estReserveeAuxJoueurs()));
    }

    /** Position de l'étape dans le parcours de ce type d'enveloppe, à partir de 1 (0 si l'étape n'en fait pas partie). */
    public function position(DepouillementType $type): int
    {
        $position = array_search($this, self::pourType($type), strict: true);

        return false === $position ? 0 : $position + 1;
    }

    public function precedente(DepouillementType $type): ?self
    {
        return self::pourType($type)[$this->position($type) - 2] ?? null;
    }

    public function suivante(DepouillementType $type): ?self
    {
        return self::pourType($type)[$this->position($type)] ?? null;
    }
}
