<?php

namespace App\Enum;

trait EnumTraits
{
    // region Getters/Setters
    public static function getValues(): array
    {
        return array_column(self::cases(), 'value');
    }
    // endregion Getters/Setters

    // region Public Methods
    /**
     * Checks if value is a valid enum value.
     *
     * @param mixed $value
     */
    public static function isValid($value): bool
    {
        if (!is_subclass_of(static::class, \UnitEnum::class)) {
            return false;
        }

        return \in_array($value, static::toArray(), true);
    }

    /**
     * Returns an array of all the enum's values in the following format:
     * For backed enums:
     * Case name as key, case value as value (same format as our former toArray method prior to native enums: CONSTANT => value)
     * [
     *      'MAIN' => 'main',
     *      'TERM_ACCOUNT' => 'term_account'
     * ]
     * For basic enums:
     * Case name as key, case name as value
     * [
     *      'main' => 'main',
     *      'term_account' => 'term_account'
     * ].
     */
    public static function toArray(): array
    {
        if (is_subclass_of(static::class, \BackedEnum::class)) {
            $cases = static::cases();
            $keys = \array_column($cases, 'name');
            $values = \array_column($cases, 'value');
        } elseif (is_subclass_of(static::class, \UnitEnum::class)) {
            $cases = static::cases();
            $keys = \array_column($cases, 'name');
            $values = $keys;
        } else {
            return [];
        }

        return \array_combine($keys, $values);
    }
    // endregion Public Methods
}
