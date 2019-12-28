<?php
namespace tachyon\traits;

use tachyon\dic\Container,
    ErrorException;

/**
 * Содержит полезные функции для работы со списками для Repository
 *
 * @author Андрей Сердюк
 * @copyright (c) 2019 IMND
 */
trait RepositoryListTrait
{
    /**
     * Список для select`а из массива строк таблицы $items
     * @return array
     */
    public function getAllSelectList()
    {
        return $this->getSelectList($this->findAllRaw());
    }

    /**
     * Список для select`а из массива строк таблицы $items
     *
     * @param $items array Массив строк таблицы
     * @return array
     */
    public function getSelectList($items, $emptyVal='...')
    {
        $pkField = $this->pkField ?? 'id';
        $valueKey = $this->valueField ?? 'value';
        $retArr = array();
        if ($emptyVal!==false) {
            $retArr[] = [
                $pkField => '',
                $valueKey => $emptyVal
            ];
        }
        foreach ($items as $item) {
            $retArr[] = [
                $pkField => $item[$pkField],
                $valueKey => $this->_getItemValue($item)
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
        $pkField = $this->pkField ?? 'id';
        $valueKey = $this->valueField ?? 'value';
        if (is_array($valueKey)) {
            throw new ErrorException($this->msg->i18n('Method RepositoryListTrait::getSelectListFromArr is not work if valueField is an array.'));
        }
        $items = array();
        foreach ($array as $key => $value) {
            $items[] = [
                $pkField => $keyIndexed ? $key : $value,
                $valueKey => $value,
            ];
        }

        return $items;
    }

    /**
     * Список значений да/нет для select`а
     *
     * @return array
     */
    public function getYesNoListData()
    {
        $valueKey = $this->valueField ?? 'value';
        $pkField = $this->pkField ?? 'id';
        return $this->getSelectList([
            [
                $pkField => true,
                $valueKey => 'да',
            ],
            [
                $pkField => false,
                $valueKey => 'нет',
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
        $valueField = $this->valueField ?? 'name';
        $valsGlue = $this->valsGlue ?? ',';
        if (is_array($valueField)) {
            $retArr = array();
            foreach ($valueField as $fieldName) {
                $retArr[] = $item[$fieldName];
            }
            return implode($valsGlue, $retArr);
        } else {
            return $item[$valueField];
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
