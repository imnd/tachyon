<?php
namespace tachyon\helpers;

/**
 * class ArrayHelper
 * Содержит полезные функции для работы с массивами
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
class ArrayHelper
{
    /**
     * @param string $value
     * @return string
     */
    public static function filterText($value)
    {
        $value = htmlentities($value);
        //$value = str_replace(['|', '&', ';', '$', '%', '@', "\\'", "'", '\\"', '"', '\\', '<', '>', '(', ')', ',', "\x27", "\x22", "\x60", "\t", "\n", "\r"], '', $value);
        return $value;
    }

    public static function convertObjToArr($object)
    {
        $tmpArr = array();
        $object = (array)$object;
        foreach ($object as $key => $value) {
            if (is_object($value)) {
                $tmpArr[$key] = self::convertObjToArr($value);    
            } elseif (is_array($value)){
                $tmpArr[$key] = self::convertObjToArr($value);
            } else {
                $tmpArr[$key] = $value;
            }
        }
        return $tmpArr;
    }

    /**
     * Суммирует значения, извлекаемые из ассоциативного массива по ключу $key
     */
    public static function sum($array, $key)
    {
        $result = 0;
        foreach ($array as $item)
            $result += is_array($item) ? $item[$key] : $item->$key;

        return $result;
    }

    /**
     * Превращает массив ассоциативных массивов в обычный, извлекая из ассоциативных
     * массивов значения по ключу $key
     */
    public static function flatten($array, $key)
    {
        $result = array();
        if (is_array($key)) {
            $ind = array_keys($key)[0];
            $key = array_values($key)[0];
        } else
            $i = 0;

        foreach ($array as $item)
            if ($value = self::_extractValue($item, $key)) {
                $resultKey = isset($i) ? $i++ : self::_extractValue($item, $ind);
                $result[$resultKey] = $value;
            }

        return $result;
    }

    private static function _extractValue($item, $key)
    {
        return is_array($item) ? $item[$key] : $item->$key;
    }

    /**
     * 
     * @param mixed $from
     * @param mixed $to
     * @return void
     */
    public static function changeKey(&$arr, $from, $to)
    {
        $arr[$to] = $arr[$from];
        unset($arr[$from]);
    }

    /**
     * Выбирает максимальное значение поля $key массива $array
     */
    public static function max($array, $key)
    {
        $array = self::flatten($array, $key);
        return max($array);
    }

    /**
     * Выбирает минимальное значение поля $key массива $array
     */
    public static function min($array, $key)
    {
        $array = self::flatten($array, $key);
        return min($array);
    }

    public static function transposeArray($array)
    {
        $transposed = array();
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
}