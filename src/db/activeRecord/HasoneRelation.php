<?php
namespace tachyon\db\activeRecord;

use tachyon\components\Message;
use tachyon\db\Alias;

/**
 * class HasoneRelation
 * Класс реализующий связь "имеет один" между моделями
 *
 * @author Андрей Сердюк
 * @copyright (c) 2020 IMND
 */
class HasoneRelation extends Relation
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
        if (is_array($this->linkKey)) {
            $thisTableLinkKey = $this->linkKey[0];
            $joinTableLinkKey = $this->linkKey[1];
        } else {
            $thisTableLinkKey = $this->pkName;
            $joinTableLinkKey = $this->linkKey;
        }
        $this->join->leftJoin(
            "{$this->tableName} AS {$this->tableAlias}",
            "{$this->tableAlias}.$thisTableLinkKey={$owner->getTableName()}.$joinTableLinkKey",
            $this->getTableAlias()
        );
    }

    public function attachWithObject($retItem, $with)
    {
        $retItem->$with = $this->model;

        return $retItem;
    }
}