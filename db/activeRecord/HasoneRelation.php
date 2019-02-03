<?php
namespace tachyon\db\activeRecord;

/**
 * class HasoneRelation
 * Класс реализующий связь "имеет один" между моделями
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
class HasoneRelation extends Relation
{
    public function joinWith($owner)
    {
        if (is_array($this->linkKey)) {
            $thisTableLinkKey = $this->linkKey[0];
            $joinTableLinkKey = $this->linkKey[1];
        } else {
            $thisTableLinkKey = $this->pkName;
            $joinTableLinkKey = $this->linkKey;
        }
        $this->get('join')->leftJoin("{$this->tableName} AS {$this->tableAlias}", "{$this->tableAlias}.$thisTableLinkKey={$owner->getTableName()}.$joinTableLinkKey", $this->getTableAlias(), $owner);
    }

    public function attachWithObject($retItem, $with)
    {
        $retItem->$with = $this->model;
        return $retItem;
    }
}