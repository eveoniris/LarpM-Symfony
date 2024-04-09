<?php

namespace App\Service;

final class Utilities
{
    public static function stable_uasort(array &$array, $value_compare_func): true
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

    public static function sortBy($a, $b): int
    {
        return $a <=> $b;
    }

    public static function sortByDesc($a, $b): int
    {
        return self::sortBy($a, $b) * -1;
    }


}
