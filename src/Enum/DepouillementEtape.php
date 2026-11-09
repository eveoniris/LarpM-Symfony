<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Étapes du parcours de dépouillement (formulaire par étapes, mobile et web).
 *
 * L'enveloppe de groupe n'a pas les étapes « Actions de fin d'Opus », « Déclaration » et « Conceptions », propres aux joueurs : la
 * position et l'étape suivante ou précédente dépendent donc du type d'enveloppe.
 *
 * Le joueur qui déclare son propre retour (le « déclarant ») ne parcourt que les étapes qui lui sont ouvertes : le contenu
 * physique de l'enveloppe (photos, étiquettes, contenu, actions) reste réservé au dépouillement.
 */
enum DepouillementEtape: string
{
    use EnumTraits;

    case IDENTIFICATION = 'identification';
    case PHOTOS = 'photos';
    case ETIQUETTES = 'etiquettes';
    case CONTENU = 'contenu';
    case ACTIONS = 'actions';
    case DECLARATION = 'declaration';
    case CONCEPTIONS = 'conceptions';
    case RETOUR_GROUPE = 'retour-groupe';
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
            self::DECLARATION => 'Déclaration du joueur',
            self::CONCEPTIONS => 'Conceptions',
            self::RETOUR_GROUPE => 'Retour du groupe',
            self::REMARQUES => 'Remarques',
            self::RECAPITULATIF => 'Récapitulatif',
        };
    }

    /** Étape propre aux enveloppes de joueurs. */
    public function estReserveeAuxJoueurs(): bool
    {
        return \in_array($this, [self::ACTIONS, self::DECLARATION, self::CONCEPTIONS], strict: true);
    }

    /** Étape propre aux enveloppes de groupe. */
    public function estReserveeAuxGroupes(): bool
    {
        return self::RETOUR_GROUPE === $this;
    }

    /** Étape ouverte au joueur qui déclare son retour (le contenu physique de l'enveloppe lui reste fermé). */
    public function estOuverteAuDeclarant(): bool
    {
        return \in_array($this, [self::DECLARATION, self::CONCEPTIONS, self::RETOUR_GROUPE, self::REMARQUES, self::RECAPITULATIF], strict: true);
    }

    /**
     * Étapes du parcours d'un type d'enveloppe, dans l'ordre (réduites à celles du déclarant quand c'est lui qui parcourt).
     *
     * @return list<self>
     */
    public static function pourType(DepouillementType $type, bool $declarant = false): array
    {
        return array_values(array_filter(
            self::cases(),
            static fn (self $etape): bool => (
                (DepouillementType::JOUEUR === $type || !$etape->estReserveeAuxJoueurs())
                && (DepouillementType::GROUPE === $type || !$etape->estReserveeAuxGroupes())
                && (!$declarant || $etape->estOuverteAuDeclarant())
            ),
        ));
    }

    /** Position de l'étape dans le parcours de ce type d'enveloppe, à partir de 1 (0 si l'étape n'en fait pas partie). */
    public function position(DepouillementType $type, bool $declarant = false): int
    {
        $position = array_search($this, self::pourType($type, $declarant), strict: true);

        return false === $position ? 0 : $position + 1;
    }

    public function precedente(DepouillementType $type, bool $declarant = false): ?self
    {
        return self::pourType($type, $declarant)[$this->position($type, $declarant) - 2] ?? null;
    }

    public function suivante(DepouillementType $type, bool $declarant = false): ?self
    {
        return self::pourType($type, $declarant)[$this->position($type, $declarant)] ?? null;
    }
}
