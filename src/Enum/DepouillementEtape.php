<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Étapes du parcours de dépouillement (formulaire par étapes, mobile et web).
 *
 * Les étapes photos et contenu sont des emplacements : leur contenu arrive avec les étapes 5 à 7 du plan.
 */
enum DepouillementEtape: string
{
    use EnumTraits;

    case IDENTIFICATION = 'identification';
    case PHOTOS = 'photos';
    case CONTENU = 'contenu';
    case REMARQUES = 'remarques';
    case RECAPITULATIF = 'recapitulatif';

    public function label(): string
    {
        return match ($this) {
            self::IDENTIFICATION => 'Enveloppe',
            self::PHOTOS => 'Photos',
            self::CONTENU => 'Contenu',
            self::REMARQUES => 'Remarques',
            self::RECAPITULATIF => 'Récapitulatif',
        };
    }

    /** Position de l'étape dans le parcours, à partir de 1. */
    public function position(): int
    {
        return (int) array_search($this, self::cases(), strict: true) + 1;
    }

    public function precedente(): ?self
    {
        return self::cases()[$this->position() - 2] ?? null;
    }

    public function suivante(): ?self
    {
        return self::cases()[$this->position()] ?? null;
    }
}
