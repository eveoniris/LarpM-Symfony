<?php

namespace App\Enum;

enum SortLevel: string
{
    use EnumTraits;

    case APPRENTICE = 'SORT APPRENTI'; // Apprenti
    case INITIATED = 'SORT INITIE'; // Initié
    case EXPERT = 'SORT EXPERT'; // Expert
    case MASTER = 'SORT MAITRE'; // Maitre
    case GRAND_MASTER = 'SORT GRAND MAITRE'; // Grand Maitre

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

    public static function getFromLabel(string $label): ?SortLevel
    {
        if ($type = self::tryFrom($label)) {
            return $type;
        }

        return null;
    }
}
