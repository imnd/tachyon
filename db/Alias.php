<?php
namespace tachyon\db;

/**
 * Класс отвечающий за подбор псевдонимов таблиц
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
class Alias extends \tachyon\Component
{
    const PK_GLUE = '____';
    /**
     * обозначение первичного ключа
     * используется т/ж в представлениях
     */
    const PK_MARKER = '_pk';

    /**
     * Приделывает название таблицы вначале и объявляет алиас
     * (заданный или сгенерированный) для избежания конфликта имен.
     */
    public function aliasField($field, $alias, $suffix='')
    {
        $field = $this->aliasFields(array($field), $alias, $suffix);
        return $field[0];
    }

    /**
     * Приделывает название таблицы вначале и объявляет алиас
     * (заданный или сгенерированный) для избежания конфликта имен.
     */
    public function aliasFields($fields, $alias, $suffix='')
    {
        $alias .= $suffix;
        return array_map(
            function($key, $val) use ($alias, $suffix) {
                if (is_numeric($key))
                    $field = trim($val);
                else
                    $field = trim($key);

                $output = $field;
                // название таблицы вначале
                if (strpos($val, '.')===false && strpos($val, '(')===false)
                    $output = "$alias.$output";
                // алиас
                if (!is_numeric($key))
                    $output .= " AS $val";
                elseif ($suffix!=='')
                    $output .= " AS $field$suffix";
                
                return $output;
            },
            array_keys($fields),
            array_values($fields)
        );
    }

    public function prependTableNameOnWhere($alias, $where)
    {
        $whereRet = array();
        foreach ($where as $field => $value) {
            if (preg_match('/[.( ]/', $field)===0)
                $field = "$alias.$field";

            $whereRet[$field] = $value;
        }
        $this->domain->where($whereRet);
    }
    
    /**
     * алиасим имена таблиц в groupBy
     */
    public function aliasGroupByTableName($tableAliases)
    {
        $groupBy = $this->domain->getGroupBy();
        $groupByArr = explode('.', $groupBy);
        // если есть имя таблицы
        if (count($groupByArr)>1) {
            // имя таблицы
            $tableName = $groupByArr[0];
            if (isset($tableAliases[$tableName])) {
                // алиасим
                $tableAlias = $tableAliases[$tableName];
                $groupByAlias = $tableAlias . '.' . $groupByArr[1];
                // засовываем
                $this->domain->groupBy($groupByAlias);
            }
        }
    }
    
    /**
     * алиасим имена таблиц в полях
     */
    public function aliasSelectTableNames($tableAliases)
    {
        $modelFields = $this->domain->getSelect();
        $modelFields = $this->_aliasArrayValues($modelFields, array_keys($tableAliases), array_values($tableAliases));
        $this->domain->select($modelFields);
    }

    /**
     * алиасим имена таблиц в условиях
     */
    public function aliasWhereTableNames($tableAliases)
    {
        $where = $this->domain->getWhere();
        $where = $this->_aliasArrayKeys($where, array_keys($tableAliases), array_values($tableAliases));
        $this->domain->where($where);
    }

    /**
     * алиасим имена таблиц в sortBy
     */
    public function aliasSortByTableName($tableAliases)
    {
        $sortBy = $this->domain->getSortBy();
        $sortBy = $this->_aliasArrayKeys($sortBy, array_keys($tableAliases), array_values($tableAliases));
        $this->domain->setSortBy($sortBy);
    }

    /**
     * выделяет алиас из поля если есть
     */
    public function getAliases($fields)
    {
        foreach ($fields as &$field) {
            $fieldArr = explode(' AS ', $field);
            if (count($fieldArr)>1)
                $field = trim($fieldArr[1]);
        }
        return $fields;
    }

    /**
     * алиас первичного ключа
     */
    public function getPrimKeyAliasArr($with, $primKey=null)
    {
        if (!$primKey)
            throw new \Exception('Не объявлен первичный ключ связанной таблицы');

        $primKeyAlias = array();
        // ключ может быть составным
        if (is_array($primKey)) {
            $i = 0;
            foreach ($primKey as $primKeyItem)
                $primKeyAlias[$primKeyItem] = $with . self::PK_MARKER . self::PK_GLUE . $i++;
        } else
            $primKeyAlias[$primKey] = $with . self::PK_MARKER;

        return $primKeyAlias;
    }

    /**
     * приклеиваем "тип" к ключам массива
     */
    public function appendSuffixToKeys($fields, $suffix)
    {
        $primKeyMarker = self::PK_MARKER;
        $primKeyMarkerLen = strlen($primKeyMarker); // для быстроты
        $glue = self::PK_GLUE;
        return array_combine(array_map(
            function($key) use ($suffix, $primKeyMarker, $primKeyMarkerLen, $glue) {
                if (strpos($key, $glue)!==false)
                    return $key;
                
                if (substr($key, -$primKeyMarkerLen)!==$primKeyMarker)
                    return $key . $suffix;
            },
            array_keys($fields)
        ), array_values($fields));
    }

    /**
     * отрезаем "тип" от ключей массива
     */
    public function trimSuffixes($fields, $type, $with='')
    {
        return array_combine(array_map(
            function($key) use ($type, $with) {
                return strtr($key, array($with . $type => '', $type => ''));
            },
            array_keys($fields)
        ), array_values($fields));
    }

    private function _aliasArrayKeys($array, $fromArray, $toArray)
    {
        foreach ($array as $key => $value) {
            if (strpos($key, '.')===false)
                continue;
            // алиасим
            $keyAlias = str_replace($fromArray, $toArray, $key);
            // засовываем в массив
            $array[$keyAlias] = $value;
            // убираем старый ключ
            if ($keyAlias!==$key)
                unset($array[$key]);
        }
        return $array;
    }
    
    private function _aliasArrayValues($array, $fromArray, $toArray)
    {
        if (count($array)==0)
            return $array;
        
        return array_combine(
            array_keys($array),
            array_map(
                function($key, $val) use ($fromArray, $toArray) {
                    if (strpos($val, '.')===false)
                        return $val;
                    return str_replace($fromArray, $toArray, $val);
                },
                array_keys($array),
                array_values($array)
            )
        );
    }
}