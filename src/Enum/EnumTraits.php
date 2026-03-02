<?php

declare(strict_types=1);

namespace App\Enum;

use BackedEnum;
use UnitEnum;

trait EnumTraits
{
    // region Getters/Setters
    /** @return list<string> */
    public static function getValues(): array
    {
        return array_column(self::cases(), 'value');
    }

    // endregion Getters/Setters

    // region Public Methods
    /**
     * Checks if value is a valid enum value.
     */
    public static function isValid(mixed $value): bool
    {
        if (!is_subclass_of(static::class, UnitEnum::class)) {
            return false;
        }

        return \in_array($value, static::toArray(), true);
    }

    /**
     * Returns an array of all the enum's values in the following format:
     * For backed enums:
     * Case name as key, case value as value (same format as our former toArray method prior to native enums: CONSTANT => value)
     * For basic enums:
     * Case name as key, case name as value
     */
    /** @return array<string, string> */
    public static function toArray(): array
    {
        if (is_subclass_of(static::class, BackedEnum::class)) {
            return array_combine(array_column(static::cases(), 'name'), array_column(static::cases(), 'value'));
        }

        if (is_subclass_of(static::class, UnitEnum::class)) {
            return array_combine(array_column(static::cases(), 'name'), array_column(static::cases(), 'name'));
        }

        return [];
    }

    // endregion Public Methods
}
