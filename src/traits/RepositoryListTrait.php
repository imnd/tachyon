<?php

namespace tachyon\traits;

use ErrorException;
use ReflectionException;
use tachyon\dic\Container;
use tachyon\exceptions\ContainerException;

/**
 * Содержит полезные функции для работы со списками для Repository
 *
 * @author Андрей Сердюк
 * @copyright (c) 2020 IMND
 */
trait RepositoryListTrait
{
    protected $pkField = 'id';
    protected $valueField = 'name';
    protected $emptyVal = '...';
    protected $valsGlue = ',';

    /**
     * Список для select`а из массива строк таблицы $items
     *
     * @param string $valueField
     *
     * @return array
     * @throws ContainerException
     * @throws ReflectionException
     */
    public function getAllSelectList($valueField = 'name'): array
    {
        $this->valueField = $valueField;
        $model = (new Container)->get(static::class);
        return $model->getSelectList($model->findAllRaw());
    }

    /**
     * Список для select`а из массива строк таблицы $items
     *
     * @param array $items Массив строк таблицы
     *
     * @return array
     */
    public function getSelectList(array $items): array
    {
        $retArr = [];
        if ($this->emptyVal !== false) {
            $retArr[] = [
                'value' => '',
                'contents' => $this->emptyVal,
            ];
        }
        foreach ($items as $item) {
            $retArr[] = [
                'value' => $item[$this->pkField],
                'contents' => $this->_getItemValue($item),
            ];
        }
        return $retArr;
    }

    /**
     * Список для select`а из произвольного массива $array
     *
     * @param array   $array
     * @param boolean $keyIndexed индексировать ключами или значениями массива
     * @param string  $emptyVal
     *
     * @return array
     * @throws ErrorException
     */
    public function getSelectListFromArr(
        array $array,
        bool $keyIndexed = false,
        string $emptyVal = '...'
    ): array {
        if (is_array($this->valueField)) {
            throw new ErrorException(
                $this->msg->i18n(
                    'Method RepositoryListTrait::getSelectListFromArr is not work if valueField is an array.'
                )
            );
        }
        $items = [];
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
    public function getYesNoListData(): array
    {
        return $this->getSelectList(
            [
                [
                    $this->pkField => true,
                    $this->valueField => 'да',
                ],
                [
                    $this->pkField => false,
                    $this->valueField => 'нет',
                ],
            ]
        );
    }

    /**
     * Список значений поля $fieldName из массива $items
     *
     * @param array  $items
     * @param string $fieldName
     *
     * @return array
     */
    public function getValsList(array $items, string $fieldName): array
    {
        $retArr = [];
        foreach ($items as $item) {
            $retArr[] = $item[$fieldName];
        }
        return $retArr;
    }

    /**
     * @param array $item
     *
     * @return string
     */
    private function _getItemValue(array $item): string
    {
        if (is_array($this->valueField)) {
            $retArr = [];
            foreach ($this->valueField as $fieldName) {
                $retArr[] = $item[$fieldName];
            }
            return implode($this->valsGlue, $retArr);
        }
        return $item[$this->valueField];
    }

    # SETTERS

    /**
     * @param string $valueField
     *
     * @return void
     */
    public function setValueField(string $valueField): void
    {
        $this->valueField = $valueField;
    }

    /**
     * @param string $valsGlue
     *
     * @return void
     */
    public function setValsGlue(string $valsGlue): void
    {
        $this->valsGlue = $valsGlue;
    }

    /**
     * @param string $pkField
     *
     * @return void
     */
    public function setPkField(string $pkField): void
    {
        $this->pkField = $pkField;
    }

    /**
     * @param string $emptyVal
     *
     * @return void
     */
    public function setEmptyVal(string $emptyVal): void
    {
        $this->emptyVal = $emptyVal;
    }
}
