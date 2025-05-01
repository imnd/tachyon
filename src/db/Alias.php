<?php

namespace tachyon\db;

use tachyon\{
    exceptions\ModelException,
    traits\HasOwner
};

/**
 * Class responsible for table aliases
 *
 * @author imndsu@gmail.com
 */
class Alias
{
    use HasOwner;

    const PK_GLUE = '____';
    /**
     * primary key designation
     * it also used in views
     */
    const PK_MARKER = '_pk';

    /**
     * Attach table name at start and declares alias (specified or generated) to avoid conflict of names.
     */
    public function aliasField(string $field, string $alias, string $suffix = ''): mixed
    {
        $field = $this->aliasFields([$field], $alias, $suffix);
        return $field[0];
    }

    /**
     * Attach table name at start and declares alias (specified or generated) to avoid conflict of names.
     */
    public function aliasFields(array $fields, string $alias, string $suffix = ''): array
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
                // table name in the beginning
                if (!str_contains($val, '.') && !str_contains($val, '(')) {
                    $output = "$alias.$output";
                }
                // alias
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

    public function prependTableNameOnWhere(string $alias, array $where): void
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
     * alias tables names in groupBy
     */
    public function aliasGroupByTableName(array $tableAliases): void
    {
        $groupBy = $this->owner->getGroupBy();
        $groupByArr = explode('.', $groupBy);
        // if there is a table name
        if (count($groupByArr) > 1) {
            // table name
            $tableName = $groupByArr[0];
            if (isset($tableAliases[$tableName])) {
                $tableAlias = $tableAliases[$tableName];
                $groupByAlias = $tableAlias . '.' . $groupByArr[1];
                $this->owner->groupBy($groupByAlias);
            }
        }
    }

    /**
     * alias tables names in fields
     */
    public function aliasSelectTableNames(array $tableAliases): void
    {
        $modelFields = $this->owner->getSelect();
        $modelFields = $this->_aliasArrayValues($modelFields, array_keys($tableAliases), array_values($tableAliases));
        $this->owner->select($modelFields);
    }

    /**
     * alias tables names in conditions
     */
    public function aliasWhereTableNames(array $tableAliases): void
    {
        $where = $this->owner->getWhere();
        $where = $this->_aliasArrayKeys($where, array_keys($tableAliases), array_values($tableAliases));
        $this->owner->where($where);
    }

    /**
     * alias tables names in sortBy
     */
    public function aliasSortByTableName(array $tableAliases): void
    {
        $sortBy = $this->owner->getSortBy();
        $sortBy = $this->_aliasArrayKeys($sortBy, array_keys($tableAliases), array_values($tableAliases));
        $this->owner->setSortBy($sortBy);
    }

    /**
     * allocates alias from the field if there is
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
     * the primary key alias
     *
     * @throws ModelException
     */
    public function getPrimKeyAliasArr(string $with, string | array $pkName = null): array
    {
        if (!$pkName) {
            throw new ModelException(t('The primary key of the related table is not declared.'));
        }
        $primKeyAlias = [];

        // the key can be composite
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
     * attach the "type" to the keys of the array
     */
    public function appendSuffixToKeys(array $fields, string $suffix): array
    {
        $primKeyMarker = self::PK_MARKER;
        $primKeyMarkerLen = strlen($primKeyMarker);
        $glue = self::PK_GLUE;
        return array_combine(
            array_map(
                static function ($key) use ($suffix, $primKeyMarker, $primKeyMarkerLen, $glue) {
                    if (str_contains($key, $glue)) {
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
     * cut the "type" from the keys of the array
     */
    public function trimSuffixes(array $fields, string $type, string $with = ''): array
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

    private function _aliasArrayKeys(array $array, array $from, array $to): array
    {
        foreach ($array as $key => $value) {
            if (!str_contains($key, '.')) {
                continue;
            }
            // алиасим
            $keyAlias = str_replace($from, $to, $key);
            // засовываем в массив
            $array[$keyAlias] = $value;
            // убираем старый ключ
            if ($keyAlias !== $key) {
                unset($array[$key]);
            }
        }
        return $array;
    }

    private function _aliasArrayValues(array $array, array $from, array $to): array
    {
        if (count($array) === 0) {
            return $array;
        }
        return array_combine(
            array_keys($array),
            array_map(
                static function ($key, $val) use ($from, $to) {
                    if (!str_contains($val, '.')) {
                        return $val;
                    }
                    return str_replace($from, $to, $val);
                },
                array_keys($array),
                array_values($array)
            )
        );
    }
}
