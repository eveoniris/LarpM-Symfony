<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Mode d'exécution de la reprise du bonus d'origine.
 */
enum OrigineBonusRepriseMode: string
{
    use EnumTraits;

    /** Analyse seule, rien n'est écrit. */
    case SIMULATION = 'simulation';
    /** Enregistre toutes les décisions. */
    case TOUT = 'tout';
    /** Enregistre sauf les cas douteux, qui restent sur l'ancien calcul. */
    case SANS_DOUTEUX = 'sans_douteux';

    public function isApply(): bool
    {
        return self::SIMULATION !== $this;
    }
}
