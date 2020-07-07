<?php
namespace tachyon\db\activeRecord;

use tachyon\db\activeRecord\Join;

/**
 * class Relation
 * Класс реализующий связи между моделями
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
class JoinRelation extends Relation
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
        $model = $this->get($this->modelName);
        $this->join->leftJoin(array(
            $this->tableAlias => $model::getSource()
        ), array($owner->getPkName(), $this->linkKey), $this->getTableAlias(), $owner);
    }

    public function attachWithObject($retItem, $with)
    {
        $retItem->$with = $this->model;
        return $retItem;
    }
}