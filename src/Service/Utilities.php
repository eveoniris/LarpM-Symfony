<?php

declare(strict_types=1);

namespace App\Service;

final class Utilities
{
    /** @param array<mixed, mixed> $array */
    public static function stable_uasort(array &$array, callable $value_compare_func): true
    {
        $index = 0;
        foreach ($array as &$item) {
            $item = [$index++, $item];
        }
        $result = uasort($array, static function ($a, $b) use ($value_compare_func) {
            $result = $value_compare_func($a[1], $b[1]);

            return 0 === $result ? $a[0] - $b[0] : $result;
        });
        foreach ($array as &$item) {
            $item = $item[1];
        }

        return $result;
    }

    public static function sortBy(mixed $a, mixed $b): int
    {
        return $a <=> $b;
    }

    public static function sortByDesc(mixed $a, mixed $b): int
    {
        return self::sortBy($a, $b) * -1;
    }
}
