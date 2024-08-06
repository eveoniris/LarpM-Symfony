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

    public function getId(): int
    {
        return self::getTypeId($this);
    }

    public function getIndex(): int
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

    public static function getFromLabel(string $label): ?LevelType
    {
        if ($type = static::tryFrom($label)) {
            return $type;
        }

        return LevelType::tryFromOlder($label);
    }

    public static function tryFromOlder(string $value): ?LevelType
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
}
