<?php
namespace tachyon\db\activeRecord;

use tachyon\components\Message;
use tachyon\db\Alias;

/**
 * Класс, реализующий связь "имеет много" между моделями
 *
 * @author imndsu@gmail.com
 */
class HasManyRelation extends Relation
{
    /**
     * @var Join $join
     */
    protected Join $join;

    public function __construct(Message $msg, Alias $alias, Join $join)
    {
        parent::__construct($msg, $alias);

        $this->join = $join;
    }

    public function joinWith($owner): void
    {
        $this->join->leftJoin(
            "{$this->tableName} AS {$this->tableAlias}",
            "{$this->tableAlias}.{$this->linkKey}={$owner->getTableName()}.{$owner->getPkName()}",
            $this->getTableAlias()
        );
    }

    public function attachWithObject($retItem, $with)
    {
        if (is_null($retItem->$with)) {
            $retItem->$with = [$this->model];
        } else {
            $retItem->$with = array_merge($retItem->$with, [$this->model]);
        }
        return $retItem;
    }
}
