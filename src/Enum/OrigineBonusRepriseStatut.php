<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Résultat de l'analyse d'un personnage existant par la commande de reprise du bonus d'origine.
 */
enum OrigineBonusRepriseStatut: string
{
    use EnumTraits;

    /** Décision déjà enregistrée. */
    case DEJA = 'deja';
    /** L'origine ne porte aucun bonus NATIVE : rien à décider. */
    case SANS_BONUS = 'sans_bonus';
    /** Pas de groupe à la première participation : décision différée. */
    case DIFFERE = 'differe';
    /** Un fief du groupe est l'origine du personnage. */
    case ACQUIS = 'acquis';
    /** Aucun fief commun. */
    case REFUSE = 'refuse';

    /** Une décision serait enregistrée par la reprise. */
    public function isDecidable(): bool
    {
        return self::ACQUIS === $this || self::REFUSE === $this;
    }
}
