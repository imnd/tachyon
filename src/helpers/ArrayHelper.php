<?php

namespace tachyon\helpers;

/**
 * @author imndsu@gmail.com
 */
class ArrayHelper
{
    public static function transposeArray(array $array): array
    {
        $transposed = [];
        foreach ($array as $key => $params) {
            if (!is_array($params)) {
                continue;
            }
            foreach ($params as $num => $val) {
                $transposed[$num][$key] = $val;
            }
        }
        return $transposed;
    }

    /**
     * selects the maximum value of the field $key, array $array
     */
    public static function max(array $array, mixed $key): mixed
    {
        $array = self::extract($array, $key);
        return max($array);
    }

    /**
     * selects the minimum value of the field $key, array $array
     */
    public static function min(array $array, mixed $key): mixed
    {
        $array = self::extract($array, $key);
        return min($array);
    }

    /**
     * sums the values retrieved from an associative array key $key
     */
    public static function sum(array $array, mixed $key): int
    {
        $result = 0;
        foreach ($array as $item) {
            $result += is_array($item) ? $item[$key] : $item->$key;
        }
        return $result;
    }

    /**
     * extracts from the associative array of values by key $key
     */
    public static function extract(array $array, mixed $key): array
    {
        if (is_array($key)) {
            $ind = key($key);
            $key = current($key);
        } else {
            $i = 0;
        }
        $result = [];
        foreach ($array as $item) {
            if ($value = self::extractValue($item, $key)) {
                $resultKey = isset($i) ? $i++ : self::extractValue($item, $ind);
                $result[$resultKey] = $value;
            }
        }
        return $result;
    }

    private static function extractValue($item, $key)
    {
        return is_array($item) ? $item[$key] : $item->$key;
    }

    public static function changeKey(array &$array, mixed $from, mixed $to): void
    {
        $array[$to] = $array[$from];
        unset($array[$from]);
    }
}
