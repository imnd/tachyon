<?php

namespace tachyon\db\dbal\conditions;

/**
 * InsertBuilder
 *
 * @author Андрей Сердюк
 * @copyright (c) 2021 IMND
 */
class InsertBuilder extends ConditionsBuilder
{
    /**
     * @inheritDoc
     */
    public function prepareConditions(
        array $conditions,
        string $operator = '='
    ): array
    {
        return $this->createConditions($conditions, '', '', ',');
    }
}
