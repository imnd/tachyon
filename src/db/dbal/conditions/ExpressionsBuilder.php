<?php

namespace tachyon\db\dbal\conditions;

/**
 * ConditionsBuilder
 *
 * @author Андрей Сердюк
 * @copyright (c) 2021 IMND
 */
abstract class ConditionsBuilder
{
    /**
     * Форматирует условия для выборки, вставки или удаления
     *
     * @param array  $conditions
     * @param string $operator
     *
     * @return array
     */
    abstract public function prepareExpression(array $conditions, string $operator = '='): array;

    /**
     * Форматирует условия для выборки, вставки или удаления
     *
     * @param array  $conditions
     * @param string $keyword
     * @param string $operator
     * @param string $glue
     *
     * @return array
     */
    protected function createExpression(
        array  $conditions,
        string $keyword,
        string $operator,
        string $glue
    ): array
    {
        $clause = '';
        $vals = [];
        if (count($conditions) !== 0) {
            $clauseArr = [];
            foreach ($conditions as $field => $val) {
                if (preg_match('/ IN/', $field, $matches) !== 0) {
                    $clauseArr[] = $this->clarifyField($field, $matches[0]) . $matches[0] . " ?";
                    $val = '(' . implode(',', $val) . ')';
                } elseif (preg_match('/ LIKE/', $field, $matches) !== 0) {
                    $clauseArr[] = $this->clarifyField($field, $matches[0]) . $matches[0] . " ?";
                    $val = "%$val%";
                } elseif (preg_match('/<=|<|>=|>/', $field, $matches) !== 0) {
                    $clauseArr[] = $this->clarifyField($field, $matches[0]) . $matches[0] . ' ?';
                } else {
                    $clauseArr[] = $this->quoteField($field) . $operator;
                }
                $vals[] = $val;
            }
            $clause = "$keyword " . implode(" $glue ", $clauseArr);
        }
        return compact('clause', 'vals');
    }

    /**
     * Очистка поля
     *
     * @param string $field
     * @param string $text
     * @return string
     */
    protected function clarifyField(string $field, string $text): string
    {
        $field = str_replace($text, '', $field);
        $field = trim($field);
        return $this->quoteField($field);
    }

    /**
     * Подготовка поля
     *
     * @param array $fields
     * @return array
     */
    public function quoteFields(array $fields): array
    {
        foreach ($fields as $key => &$field) {
            if (!is_numeric($key)) {
                $field = "$key AS $field";
            } else {
                $field = $this->quoteField($field);
            }
        }
        return $fields;
    }

    /**
     * Снабжение поля кавычками
     *
     * @param string $field
     * @return string
     */
    protected function quoteField(string $field): string
    {
        if (preg_match('/[.( ]/', $field) === 0) {
            $field = "`" . trim($field) . "`";
        }
        return $field;
    }
}
