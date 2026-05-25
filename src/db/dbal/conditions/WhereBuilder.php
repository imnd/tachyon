<?php

namespace tachyon\db\dbal\conditions;

/**
 * @author imndsu@gmail.com
 * @copyright (c) 2026 imnd labs
 */
class WhereBuilder extends ExpressionsBuilder
{
    /**
     * @inheritDoc
     */
    public function prepareExpression(array $conditions, string $operator = '='): array
    {
        return $this->createExpression($conditions, 'WHERE', "$operator ?", 'AND');
    }

    /**
     * Formats selection fields
     */
    public function prepareFields(array $fields): string
    {
        $fields = $this->quoteFields($fields);

        return $fields ? implode(',', $fields) : '*';
    }
}
