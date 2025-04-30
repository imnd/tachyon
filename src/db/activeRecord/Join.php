<?php
namespace tachyon\db\activeRecord;

use tachyon\traits\HasOwner;

/**
 * @author imndsu@gmail.com
 */
class Join
{
    use HasOwner;

    public function innerJoin($join, $on, $alias): void
    {
        $this->setJoin($join, $on, 'INNER', $alias);
    }

    public function leftJoin($join, $on, $alias): void
    {
        $this->setJoin($join, $on, 'LEFT', $alias);
    }

    public function rightJoin($join, $on, $alias): void
    {
        $this->setJoin($join, $on, 'RIGHT', $alias);
    }

    public function outerJoin($join, $on, $alias): void
    {
        $this->setJoin($join, $on, 'FULL OUTER', $alias);
    }

    /**
     * setJoin
     * Устанавливает джойн таблицы
     *
     * @param $join string | array
     *      string - название таблицы
     *      array - название таблицы => алиас
     * @param $on array по каким полям присоединяется пк => фк
     * @param $mode string тип
     * @param $alias string алиас главной таблицы запроса
     *
     * @return Join
     */
    public function setJoin($join, $on, $mode, $alias): Join
    {
        if (is_array($join)) {
            $joinKeys = array_keys($join);
            $joinVals = array_values($join);
            $tblName = $joinVals[0];
            $expr = " {$joinKeys[0]} AS $tblName ";
        } else {
            $tblName = $expr = $join;
        }
        $onCond = is_array($on) ? " $alias.{$on[0]}=$tblName.{$on[1]} " : " $on ";

        $this->owner->getDb()->addJoin($expr, $onCond, $mode);

        return $this;
    }

    /**
     * @param $join string | array
     * @return string
     */
    public function getRelationName($join)
    {
        if (is_array($join)) {
            $joinKeys = array_keys($join);
            return $joinKeys[0];
        }
        return $join;
    }
}
