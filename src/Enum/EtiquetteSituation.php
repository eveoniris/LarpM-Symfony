<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Ce que le dépouilleur constate dans l'enveloppe pour une étiquette.
 */
enum EtiquetteSituation: string
{
    use EnumTraits;

    /** L'étiquette est dans l'enveloppe avec son objet. */
    case AVEC_OBJET = 'avec_objet';
    /** L'étiquette est seule dans l'enveloppe (cas normal d'une étiquette orange). */
    case SANS_OBJET = 'sans_objet';
    /** L'objet a été remis directement au PC joueur (trop volumineux pour l'enveloppe). */
    case OBJET_REMIS_AU_PC = 'objet_remis_au_pc';

    public function label(): string
    {
        return match ($this) {
            self::AVEC_OBJET => 'Avec son objet',
            self::SANS_OBJET => 'Étiquette seule',
            self::OBJET_REMIS_AU_PC => 'Objet remis directement au PC joueur',
        };
    }
}
