<?php

namespace tachyon\db;

use tachyon\{
    exceptions\ModelException,
    components\Message,
    traits\HasOwner
};

/**
 * Класс отвечающий за подбор псевдонимов таблиц
 *
 * @author Андрей Сердюк
 * @copyright (c) 2020 IMND
 */
class Alias
{
    use HasOwner;

    const PK_GLUE = '____';
    /**
     * обозначение первичного ключа
     * используется т/ж в представлениях
     */
    const PK_MARKER = '_pk';

    /**
     * @var Message $msg
     */
    protected $msg;

    /**
     * @param Message $msg
     */
    public function __construct(Message $msg)
    {
        $this->msg = $msg;
    }

    /**
     * Приделывает название таблицы вначале и объявляет алиас
     * (заданный или сгенерированный) для избежания конфликта имен.
     *
     * @param        $field
     * @param        $alias
     * @param string $suffix
     *
     * @return mixed|string
     */
    public function aliasField($field, $alias, $suffix = '')
    {
        $field = $this->aliasFields([$field], $alias, $suffix);
        return $field[0];
    }

    /**
     * Приделывает название таблицы вначале и объявляет алиас
     * (заданный или сгенерированный) для избежания конфликта имен.
     *
     * @param        $fields
     * @param        $alias
     * @param string $suffix
     *
     * @return array|string[]
     */
    public function aliasFields($fields, $alias, $suffix = '')
    {
        $alias .= $suffix;
        return array_map(
            function ($key, $val) use ($alias, $suffix) {
                if (is_numeric($key)) {
                    $field = trim($val);
                } else {
                    $field = trim($key);
                }
                $output = $field;
                // название таблицы вначале
                if (strpos($val, '.') === false && strpos($val, '(') === false) {
                    $output = "$alias.$output";
                }
                // алиас
                if (!is_numeric($key)) {
                    $output .= " AS $val";
                } elseif ($suffix !== '') {
                    $output .= " AS $field$suffix";
                }
                return $output;
            },
            array_keys($fields),
            array_values($fields)
        );
    }

    /**
     * @param $alias
     * @param $where
     */
    public function prependTableNameOnWhere($alias, $where): void
    {
        $whereRet = [];
        foreach ($where as $field => $value) {
            if (preg_match('/[.( ]/', $field) === 0) {
                $field = "$alias.$field";
            }
            $whereRet[$field] = $value;
        }
        $this->owner->where($whereRet);
    }

    /**
     * алиасим имена таблиц в groupBy
     *
     * @param $tableAliases
     */
    public function aliasGroupByTableName($tableAliases): void
    {
        $groupBy = $this->owner->getGroupBy();
        $groupByArr = explode('.', $groupBy);
        // если есть имя таблицы
        if (count($groupByArr) > 1) {
            // имя таблицы
            $tableName = $groupByArr[0];
            if (isset($tableAliases[$tableName])) {
                // алиасим
                $tableAlias = $tableAliases[$tableName];
                $groupByAlias = $tableAlias . '.' . $groupByArr[1];
                // засовываем
                $this->owner->groupBy($groupByAlias);
            }
        }
    }

    /**
     * алиасим имена таблиц в полях
     *
     * @param $tableAliases
     */
    public function aliasSelectTableNames($tableAliases): void
    {
        $modelFields = $this->owner->getSelect();
        $modelFields = $this->_aliasArrayValues($modelFields, array_keys($tableAliases), array_values($tableAliases));
        $this->owner->select($modelFields);
    }

    /**
     * алиасим имена таблиц в условиях
     *
     * @param $tableAliases
     */
    public function aliasWhereTableNames($tableAliases): void
    {
        $where = $this->owner->getWhere();
        $where = $this->_aliasArrayKeys($where, array_keys($tableAliases), array_values($tableAliases));
        $this->owner->where($where);
    }

    /**
     * алиасим имена таблиц в sortBy
     *
     * @param $tableAliases
     */
    public function aliasSortByTableName($tableAliases): void
    {
        $sortBy = $this->owner->getSortBy();
        $sortBy = $this->_aliasArrayKeys($sortBy, array_keys($tableAliases), array_values($tableAliases));
        $this->owner->setSortBy($sortBy);
    }

    /**
     * выделяет алиас из поля если есть
     *
     * @param array $fields
     *
     * @return array
     */
    public function getAliases(array $fields): array
    {
        foreach ($fields as &$field) {
            $fieldArr = explode(' AS ', $field);
            if (count($fieldArr) > 1) {
                $field = trim($fieldArr[1]);
            }
        }
        return $fields;
    }

    /**
     * алиас первичного ключа
     *
     * @param $with
     * @param $pkName
     *
     * @return array
     * @throws ModelException
     */
    public function getPrimKeyAliasArr($with, $pkName): array
    {
        if (!$pkName) {
            throw new ModelException($this->msg->i18n('The primary key of the related table is not declared.'));
        }
        $primKeyAlias = [];
        // ключ может быть составным
        if (is_array($pkName)) {
            $i = 0;
            foreach ($pkName as $pkItem) {
                $primKeyAlias[$pkItem] = $with . self::PK_MARKER . self::PK_GLUE . $i++;
            }
        } else {
            $primKeyAlias[$pkName] = $with . self::PK_MARKER;
        }
        return $primKeyAlias;
    }

    /**
     * приклеиваем "тип" к ключам массива
     *
     * @param array $fields
     * @param string $suffix
     *
     * @return array
     */
    public function appendSuffixToKeys(array $fields, string $suffix): array
    {
        $primKeyMarker = self::PK_MARKER;
        $primKeyMarkerLen = strlen($primKeyMarker); // для быстроты
        $glue = self::PK_GLUE;
        return array_combine(
            array_map(
                static function ($key) use ($suffix, $primKeyMarker, $primKeyMarkerLen, $glue) {
                    if (strpos($key, $glue) !== false) {
                        return $key;
                    }
                    if (substr($key, -$primKeyMarkerLen) !== $primKeyMarker) {
                        return $key . $suffix;
                    }
                },
                array_keys($fields)
            ),
            array_values($fields)
        );
    }

    /**
     * отрезаем "тип" от ключей массива
     *
     * @param array  $fields
     * @param        $type
     * @param string $with
     *
     * @return array
     */
    public function trimSuffixes(array $fields, $type, string $with = ''): array
    {
        return array_combine(
            array_map(
                static function ($key) use ($type, $with) {
                    return strtr($key, [$with . $type => '', $type => '']);
                },
                array_keys($fields)
            ),
            array_values($fields)
        );
    }

    /**
     * @param array $array
     * @param $fromArray
     * @param $toArray
     *
     * @return array
     */
    private function _aliasArrayKeys(array $array, $fromArray, $toArray): array
    {
        foreach ($array as $key => $value) {
            if (strpos($key, '.') === false) {
                continue;
            }
            // алиасим
            $keyAlias = str_replace($fromArray, $toArray, $key);
            // засовываем в массив
            $array[$keyAlias] = $value;
            // убираем старый ключ
            if ($keyAlias !== $key) {
                unset($array[$key]);
            }
        }
        return $array;
    }

    /**
     * @param array $array
     * @param $fromArray
     * @param $toArray
     *
     * @return array
     */
    private function _aliasArrayValues(array $array, $fromArray, $toArray): array
    {
        if (count($array) === 0) {
            return $array;
        }
        return array_combine(
            array_keys($array),
            array_map(
                static function ($key, $val) use ($fromArray, $toArray) {
                    if (strpos($val, '.') === false) {
                        return $val;
                    }
                    return str_replace($fromArray, $toArray, $val);
                },
                array_keys($array),
                array_values($array)
            )
        );
    }
}