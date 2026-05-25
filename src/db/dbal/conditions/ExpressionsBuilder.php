<?php

namespace tachyon\db\dbal\conditions;

/**
 * ConditionsBuilder
 *
 * @author imndsu@gmail.com
 * @copyright (c) 2026 imnd labs
 */
abstract class ExpressionsBuilder
{
    /**
     * Formats conditions for selection, insertion or deletion
     */
    abstract public function prepareExpression(array $conditions, string $operator = '='): array;

    /**
     * Formats conditions for selection, insertion or deletion
     */
    protected function createExpression(
        array  $conditions,
        string $keyword,
        string $operator,
        string $glue
    ): array {
        $clause = '';
        $vals = [];
        if (count($conditions) !== 0) {
            $clauseArr = [];
            foreach ($conditions as $field => $val) {
                if (preg_match('/ IN/', $field, $matches) !== 0) {
                    $val = (array)$val;
                    $placeholders = implode(',', array_fill(0, count($val), '?'));
                    $clauseArr[] = $this->clarifyField($field, $matches[0], true) . $matches[0] . " ($placeholders)";
                    foreach ($val as $subVal) {
                        $vals[] = $subVal;
                    }
                    continue;
                } elseif (preg_match('/ LIKE/', $field, $matches) !== 0) {
                    $clauseArr[] = $this->clarifyField($field, $matches[0], true) . $matches[0] . " ?";
                    $val = "%$val%";
                } elseif (preg_match('/<=|<|>=|>/', $field, $matches) !== 0) {
                    $clauseArr[] = $this->clarifyField($field, $matches[0], true) . $matches[0] . ' ?';
                } else {
                    $clauseArr[] = $this->quoteField($field, true) . $operator;
                }
                $vals[] = $val;
            }
            $clause = "$keyword " . implode(" $glue ", $clauseArr);
        }
        return compact('clause', 'vals');
    }

    /**
     * Field cleaning
     */
    protected function clarifyField(string $field, string $text, bool $isIdentifierOnly = false): string
    {
        $field = trim( str_replace($text, '', $field) );

        return $this->quoteField($field, $isIdentifierOnly);
    }

    /**
     * Field preparation
     */
    public function quoteFields(array $fields): array
    {
        foreach ($fields as $key => &$field) {
            $field = is_numeric($key) ? $this->quoteField($field) : "$key AS $field";
        }
        return $fields;
    }

    /**
     * Quote field value
     */
    protected function
    quoteField(string $field, bool $identifierOnly = false): string
    {
        if ($identifierOnly) {
            $field = preg_replace('/[^a-zA-Z0-9_.\-`]/', '', $field);
        }
        $parts = explode('.', $field);
        foreach ($parts as &$part) {
            if ($part !== '') {
                $this->addQuotes($part);
            }
        }
        return implode('.', $parts);
    }

    private function addQuotes(&$text): void
    {
        $text = "`" . str_replace("`", "", trim($text)) . "`";
    }
}
