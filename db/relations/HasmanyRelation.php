<?php
namespace tachyon\db\relations;

/**
 * Класс, реализующий связь "имеет много" между моделями
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
class HasmanyRelation extends Relation
{
    public function joinWith($owner)
    {
        $this->get('join')->leftJoin("{$this->tableName} AS {$this->tableAlias}", "{$this->tableAlias}.{$this->linkKey}={$owner->getTableName()}.{$owner->getPrimKey()}", $this->getTableAlias(), $owner);
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
