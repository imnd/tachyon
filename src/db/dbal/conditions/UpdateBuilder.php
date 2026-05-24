<?php

namespace tachyon\db\dbal\conditions;

/**
 * UpdateBuilder
 *
 * @author imndsu@gmail.com
 * @copyright (c) 2026 imnd labs
 */
class UpdateBuilder extends ExpressionsBuilder
{
    /**
     * @inheritDoc
     */
    public function prepareExpression(
        array $conditions,
        string $operator = '='
    ): array
    {
        return $this->createExpression($conditions, 'SET', "$operator ?", ',');
    }
}
