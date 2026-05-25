<?php

namespace tachyon\db\dbal\conditions;

/**
 * WhereBuilder
 *
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
