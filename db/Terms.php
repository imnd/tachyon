<?php
namespace tachyon\db;

/**
 * Класс отвечающий за генерацию SQL выражений условий выборки
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2019 IMND
 */
class Terms extends \tachyon\Component
{
    /**
     * Устанавливает условие больше чем
     * 
     * @param $where array массив условий
     * @param $field string поле на котором устанавливается условие
     * @param $arrKey string ключ массива условий
     * @param $precise "меньше" или "меньше или равно"
     * 
     * @return ActiveRecord
     */
    public function gt($where, $field, $arrKey, $precise=false)
    {
        if (!empty($where[$arrKey])) {
            return array("$field>" . ($precise ? '' : '=') => $where[$arrKey]);
        }
    }

    /**
     * Устанавливает условие меньше чем
     * 
     * @param $where array массив условий
     * @param $field string поле на котором устанавливается условие
     * @param $arrKey string ключ массива условий
     * @param $precise "меньше" или "меньше или равно"
     * 
     * @return ActiveRecord
     */
    public function lt($where, $field, $arrKey, $precise=false)
    {
        if (!empty($where[$arrKey])) {
            return array("$field<" . ($precise ? '' : '=') => $where[$arrKey]);
        }
    }

    /**
     * Устанавливает условие LIKE
     * 
     * @param $where array 
     * @param $field string
     */
    public function like($where, $field)
    {
        if (!empty($where[$field])) {
            return array("$field LIKE" => $where[$field]);
        }
    }
}