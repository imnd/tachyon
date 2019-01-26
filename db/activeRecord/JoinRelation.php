<?php
namespace tachyon\db\activeRecord;

/**
 * class Relation
 * Класс реализующий связи между моделями
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
class JoinRelation extends Relation
{
    public function joinWith($owner)
    {
        $model = $this->get($this->modelName);
        $this->get('join')->leftJoin(array(
            $this->tableAlias => $model::getSource()
        ), array($owner->getPrimKey(), $this->linkKey), $this->getTableAlias(), $owner);
    }

    public function attachWithObject($retItem, $with)
    {
        $retItem->$with = $this->model;
        return $retItem;
    }
}