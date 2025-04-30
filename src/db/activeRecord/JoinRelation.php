<?php
namespace tachyon\db\activeRecord;

use tachyon\components\Message;
use tachyon\db\Alias;

/**
 * class Relation
 * Класс реализующий связи между моделями
 *
 * @author imndsu@gmail.com
 */
class JoinRelation extends Relation
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
        $model = $this->get($this->modelName);
        $this->join->leftJoin(
            [
                $this->tableAlias => $model->getSource()
            ],
            [$owner->getPkName(), $this->linkKey],
            $this->getTableAlias()
        );
    }

    public function attachWithObject($retItem, $with)
    {
        $retItem->$with = $this->model;
        return $retItem;
    }
}
