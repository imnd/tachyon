<?php

namespace tachyon\db\dbal\conditions;

/**
 * InsertBuilder
 *
 * @author Андрей Сердюк
 * @copyright (c) 2021 IMND
 */
class InsertBuilder extends ExpressionsBuilder
{
    /**
     * @inheritDoc
     */
    public function prepareExpression(
        array $conditions,
        string $operator = '='
    ): array
    {
        return $this->createExpression($conditions, '', '', ',');
    }
}
