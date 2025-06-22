<?php

namespace App\Enum;

enum LevelType: string
{
    use EnumTraits;

    case APPRENTICE = 'Apprentice'; // Apprenti
    case INITIATED = 'Initiated'; // Initié
    case EXPERT = 'Expert'; // Expert
    case MASTER = 'Master'; // Maitre
    case GRAND_MASTER = 'Grand master'; // Grand Maitre

    public static function getFromIndex(int $index): ?self
    {
        return match ($index) {
            1 => self::APPRENTICE,
            3 => self::EXPERT,
            5 => self::GRAND_MASTER,
            2 => self::INITIATED,
            4 => self::MASTER,
            default => null,
        };
    }

    public static function getFromLabel(string $label): ?self
    {
        if ($type = self::tryFrom($label)) {
            return $type;
        }

        return self::tryFromOlder($label);
    }

    public static function tryFromOlder(string $value): ?self
    {
        return match ($value) {
            'Apprenti' => self::APPRENTICE,
            'Initié', 'InitiÃ©' => self::INITIATED,
            'Expert' => self::EXPERT,
            'Maitre', 'MaÃ®tre', 'Maître' => self::MASTER,
            'Grand Maitre', 'Grand MaÃ®tre', 'Grand Maître' => self::GRAND_MASTER,
            default => null,
        };
    }

    public function getId(): int
    {
        return self::getTypeId($this);
    }

    public static function getTypeId(self $type): int
    {
        return match ($type->value) {
            self::APPRENTICE->value => 1,
            self::EXPERT->value => 3,
            self::GRAND_MASTER->value => 5,
            self::INITIATED->value => 2,
            self::MASTER->value => 4,
            default => -1,
        };
    }

    public function getIndex(): int
    {
        return self::getTypeId($this);
    }
}
