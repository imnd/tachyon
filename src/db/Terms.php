<?php

namespace tachyon\db;

/**
 * Трейт отвечающий за генерацию SQL выражений условий выборки
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2020 IMND
 */
trait Terms
{
    /**
     * Устанавливает условие больше чем
     *
     * @param array   $where массив условий
     * @param string  $field поле на котором устанавливается условие
     * @param string  $arrKey ключ массива условий
     * @param boolean $precise "меньше" или "меньше или равно"
     *
     * @return array
     */
    public function gt(
        array $where,
        string $field,
        string $arrKey,
        bool $precise = false
    ): array
    {
        if (!empty($where[$arrKey])) {
            return [
                "$field>" . ($precise ? '' : '=') => $where[$arrKey],
            ];
        }
        return [];
    }

    /**
     * Устанавливает условие меньше чем
     *
     * @param array   $where массив условий
     * @param string  $field поле на котором устанавливается условие
     * @param string  $arrKey ключ массива условий
     * @param boolean $precise "меньше" или "меньше или равно"
     *
     * @return array
     */
    public function lt(
        array $where,
        string $field,
        string $arrKey,
        bool $precise = false
    ): array
    {
        if (!empty($where[$arrKey])) {
            return ["$field<" . ($precise ? '' : '=') => $where[$arrKey]];
        }
        return [];
    }

    /**
     * Устанавливает условие LIKE
     *
     * @param array  $where
     * @param string $field
     */
    public function like(array $where, string $field): array
    {
        if (!empty($where[$field])) {
            return [
                "$field LIKE" => $where[$field],
            ];
        }
        return [];
    }
}