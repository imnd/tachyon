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
    /**
     * @param $object
     *
     * @return array
     */
    public function convertObjToArr($object): array
    {
        $tmpArr = [];
        $object = (array)$object;
        foreach ($object as $key => $value) {
            if (is_object($value) || is_array($value)) {
                $tmpArr[$key] = $this->convertObjToArr($value);
            } else {
                $tmpArr[$key] = $value;
            }
        }
        return $tmpArr;
    }

    /**
     * Выбирает максимальное значение поля $key массива $array
     *
     * @param array $array
     * @param mixed $key
     *
     * @return mixed
     */
    public function max(array $array, $key)
    {
        $array = $this->twitch($array, $key);
        return max($array);
    }

    /**
     * Выбирает минимальное значение поля $key массива $array
     *
     * @param $array
     * @param $key
     *
     * @return mixed
     */
    public function min($array, $key)
    {
        $array = $this->twitch($array, $key);
        return min($array);
    }

    /**
     * Суммирует значения, извлекаемые из ассоциативного массива по ключу $key
     *
     * @param $array
     * @param $key
     *
     * @return int
     */
    public function sum($array, $key): int
    {
        $result = 0;
        foreach ($array as $item) {
            $result += is_array($item) ? $item[$key] : $item->$key;
        }
        return $result;
    }

    /**
     * Извлекает из ассоциативных массивов значения по ключу $key
     *
     * @param $array
     * @param $key
     *
     * @return array
     */
    public function twitch($array, $key): array
    {
        $result = [];
        if (is_array($key)) {
            $ind = key($key);
            $key = current($key);
        } else {
            $i = 0;
        }
        foreach ($array as $item) {
            if ($value = $this->_extractValue($item, $key)) {
                $resultKey = isset($i) ? $i++ : $this->_extractValue($item, $ind);
                $result[$resultKey] = $value;
            }
        }
        return $result;
    }

    private function _extractValue($item, $key)
    {
        return is_array($item) ? $item[$key] : $item->$key;
    }

    /**
     * @param array $arr
     * @param mixed $from
     * @param mixed $to
     *
     * @return void
     */
    public function changeKey(array &$arr, $from, $to): void
    {
        $arr[$to] = $arr[$from];
        unset($arr[$from]);
    }

    /**
     * @param $array
     *
     * @return array
     */
    public function transposeArray($array): array
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
}