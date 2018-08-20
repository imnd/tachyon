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

    public static function transposeArray($array)
    {
        $transposed = array();
        foreach ($array as $key => $params)
            foreach ($params as $num => $val)
                $transposed[$num][$key] = $val;

        return $transposed;
    }
}