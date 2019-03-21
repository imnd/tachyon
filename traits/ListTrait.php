<?php
namespace tachyon\traits;

use tachyon\dic\Container;

/**
 * Содержит полезные функции для работы со списками
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
trait ListTrait
{
    /**
     * Список для select`а из массива строк таблицы $items
     * @return array
     */
    public static function getAllSelectList()
    {
        $model = (new Container)->get(get_called_class());
        return $model->getSelectList($model->findAllScalar());
    }

    /**
     * Список для select`а из массива строк таблицы $items
     * 
     * @param $items array Массив строк таблицы
     * @return array
     */
    public function getSelectList($items)
    {
        $retArr = array();
        if ($this->emptyVal!==false) {
            $retArr[] = [
                'value' => '',
                'contents' => $this->emptyVal
            ];
        }
        foreach ($items as $item) {
            $retArr[] = [
                'value' => $item[$this->pkField],
                'contents' => $this->_getItemValue($item)
            ];
        }
        return $retArr;
    }
    
    /**
     * Список для select`а из произвольного массива $array
     * 
     * @param $array array
     * @param $keyIndexed boolean индексировать ключами или значениями массива
     * 
     * @return array
     * @throws ErrorException
     */
    public function getSelectListFromArr($array, $keyIndexed=false, $emptyVal='...')
    {
        if (is_array($this->valueField)) {
            throw new \ErrorException($this->msg->i18n('Method ListTrait::getSelectListFromArr is not work if valueField is an array.'));
        }
        $items = array();
        foreach ($array as $key => $value) {
            $items[] = [
                $this->pkField => $keyIndexed ? $key : $value,
                $this->valueField => $value,
            ];
        }
        $this->emptyVal = $emptyVal;
        return $this->getSelectList($items);
    }

    /**
     * Список значений да/нет для select`а
     * 
     * @return array
     */
    public function getYesNoListData()
    {
        return $this->getSelectList([
            [
                $this->pkField => true,
                $this->valueField => 'да',
            ],
            [
                $this->pkField => false,
                $this->valueField => 'нет',
            ],
        ]);
    }

    /**
     * Список значений поля $fieldName из массива $items
     * 
     * @param $items array
     * @param $fieldName string
     * 
     * @return array
     */
    public function getValsList($items, $fieldName)
    {
        $retArr = array();
        foreach ($items as $item) {
            $retArr[] = $item[$fieldName];
        }
        return $retArr;
    }

    /**
     * @param $item array 
     * @return array
     */
    private function _getItemValue($item)
    {
        if (is_array($this->valueField)) {
            $retArr = array();
            foreach ($this->valueField as $fieldName) {
                $retArr[] = $item[$fieldName];
            }
            return implode($this->valsGlue, $retArr);
        } else {
            return $item[$this->valueField];
        }
    }

    # SETTERS

    /**
     * @param string $valueField
     * @return void
     */
    public function setValueField($valueField)
    {
        $this->valueField = $valueField;
    }

    /**
     * @param string $valsGlue
     * @return void
     */
    public function setValsGlue($valsGlue)
    {
        $this->valsGlue = $valsGlue;
    }

    /**
     * @param string $pkField
     * @return void
     */
    public function setPkField($pkField)
    {
        $this->pkField = $pkField;
    }

    /**
     * @param string $emptyVal
     * @return void
     */
    public function setEmptyVal($emptyVal)
    {
        $this->emptyVal = $emptyVal;
    }
}