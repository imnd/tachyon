<?php

namespace tachyon\db\dbal\conditions;

/**
 * WhereBuilder
 *
 * @author Андрей Сердюк
 * @copyright (c) 2021 IMND
 */
class WhereBuilder extends ConditionsBuilder
{
    /**
     * @inheritDoc
     */
    public function prepareConditions(array $conditions, string $operator = '='): array
    {
        return $this->createConditions($conditions, 'WHERE', "$operator ?", 'AND');
    }

    /**
     * Форматирует поля для выборки
     *
     * @param array $fields
     * @return string
     */
    public function prepareFields($fields): string
    {
        $fields = $this->quoteFields($fields);
        return $fields ? implode(',', $fields) : '*';
    }
}
