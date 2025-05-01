<?php

namespace tachyon\db\dbal\conditions;

/**
 * Responsible for generating sql selection queries conditions
 *
 * @author imndsu@gmail.com
 */
class Terms
{
    /**
     * Sets the condition more than
     *
     * @param array   $where   array of conditions
     * @param string  $field   the field on which the condition is established
     * @param string  $arrKey  the key of the array of conditions
     * @param boolean $precise "greater" or "greater or equal"
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
                "$field >" . ($precise ? '' : '=') => $where[$arrKey],
            ];
        }
        return [];
    }

    /**
     * Sets the condition less than
     *
     * @param array   $where   array of conditions
     * @param string  $field   the field on which the condition is established
     * @param string  $arrKey  the key of the array of conditions
     * @param boolean $precise "less" or "less or equal"
     */
    public function lt(
        array  $where,
        string $field,
        string $arrKey,
        bool   $precise = false
    ): array
    {
        if (!empty($where[$arrKey])) {
            return ["$field <" . ($precise ? '' : '=') => $where[$arrKey]];
        }
        return [];
    }

    /**
     * Sets the LIKE condition
     */
    public function like(array $where, string $field): array
    {
        if (!empty($where[$field])) {
            return [
                "$field LIKE " => $where[$field],
            ];
        }
        return [];
    }
}
