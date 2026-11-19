<?php

declare(strict_types=1);

namespace App\Enum;

/** Nature d'un ajout en masse sur une liste de personnages. */
enum AjoutMasseType: string
{
    case XP = 'xp';
    case RENOMME = 'renomme';
    case CHRONOLOGIE = 'chronologie';

    public function label(): string
    {
        return match ($this) {
            self::XP => 'XP',
            self::RENOMME => 'Renommée',
            self::CHRONOLOGIE => 'Chronologie',
        };
    }

    public function valeurLabel(): string
    {
        return self::CHRONOLOGIE === $this ? 'Année' : 'Valeur';
    }

    public function logType(): LogActionType
    {
        return match ($this) {
            self::XP => LogActionType::XP_ADD,
            self::RENOMME => LogActionType::ADD_RENOMME,
            self::CHRONOLOGIE => LogActionType::AJOUT_MASSE,
        };
    }

    /** Les personnages morts ne reçoivent pas d'XP directement : l'admin tranche cas par cas. */
    public function distingueLesMorts(): bool
    {
        return self::XP === $this;
    }
}
