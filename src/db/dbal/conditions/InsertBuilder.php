<?php

namespace tachyon\db\dbal\conditions;

/**
 * InsertBuilder
 *
 * @author imndsu@gmail.com
 * @copyright (c) 2026 imnd labs
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
