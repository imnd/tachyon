<?php
namespace tachyon\db\activeRecord;

use tachyon\db\activeRecord\Join;

/**
 * Класс, реализующий связь "имеет много" между моделями
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
class HasmanyRelation extends Relation
{
    /**
     * @var \tachyon\db\activeRecord\Join $join
     */
    protected $join;

    public function __construct(Join $join)
    {
        $this->join = $join;
    }

    public function joinWith($owner)
    {
        $this->join->leftJoin("{$this->tableName} AS {$this->tableAlias}", "{$this->tableAlias}.{$this->linkKey}={$owner->getTableName()}.{$owner->getPkName()}", $this->getTableAlias(), $owner);
    }

    public function attachWithObject($retItem, $with)
    {
        if (is_null($retItem->$with))
            $retItem->$with = array($this->model);
        else
            $retItem->$with = array_merge($retItem->$with, array($this->model));
            
        return $retItem;
    }
}
