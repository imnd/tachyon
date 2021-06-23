<?php

namespace tachyon\db\dbal\conditions;

/**
 * UpdateBuilder
 *
 * @author Андрей Сердюк
 * @copyright (c) 2021 IMND
 */
class UpdateBuilder extends ConditionsBuilder
{
    /**
     * @inheritDoc
     */
    public function prepareConditions(
        array $conditions,
        string $operator = '='
    ): array
    {
        return $this->createConditions($conditions, 'SET', "$operator ?", ',');
    }
}
