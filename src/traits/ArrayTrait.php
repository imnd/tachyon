<?php
namespace tachyon\traits;

/**
 * class ArrayHelper
 * Содержит полезные функции для работы с массивами
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2020 IMND
 */
trait ArrayTrait
{
    public function convertObjToArr($object)
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
    public function sum($array, $key)
    {
        $result = 0;
        foreach ($array as $item) {
            $result += is_array($item) ? $item[$key] : $item->$key;
        }
        return $result;
    }

    /**
     * Превращает массив ассоциативных массивов в обычный, извлекая из ассоциативных
     * массивов значения по ключу $key
     */
    public function flatten($array, $key)
    {
        $result = array();
        if (is_array($key)) {
            $ind = key($key);
            $key = current($key);
        } else {
            $i = 0;
        }
        foreach ($array as $item)
            if ($value = $this->_extractValue($item, $key)) {
                $resultKey = isset($i) ? $i++ : $this->_extractValue($item, $ind);
                $result[$resultKey] = $value;
            }

        return $result;
    }

    private function _extractValue($item, $key)
    {
        return is_array($item) ? $item[$key] : $item->$key;
    }

    /**
     * 
     * @param mixed $from
     * @param mixed $to
     * @return void
     */
    public function changeKey(&$arr, $from, $to)
    {
        $arr[$to] = $arr[$from];
        unset($arr[$from]);
    }

    /**
     * Выбирает максимальное значение поля $key массива $array
     */
    public function max($array, $key)
    {
        $array = self::flatten($array, $key);
        return max($array);
    }

    /**
     * Выбирает минимальное значение поля $key массива $array
     */
    public function min($array, $key)
    {
        $array = self::flatten($array, $key);
        return min($array);
    }

    public function transposeArray($array)
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