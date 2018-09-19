<?php
namespace tachyon\db;

/**
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
class Join extends \tachyon\Component
{
    # геттеры/сеттеры DIC
    use \tachyon\dic\Config;

    public function innerJoin($join, $on, $alias, &$owner)
    {
        $this->setJoin($join, $on, 'INNER', $alias, $owner);
    }
    
    public function leftJoin($join, $on, $alias, &$owner)
    {
        $this->setJoin($join, $on, 'LEFT', $alias, $owner);
    }
    
    public function rightJoin($join, $on, $alias, &$owner)
    {
        $this->setJoin($join, $on, 'RIGHT', $alias, $owner);
    }
    
    public function outerJoin($join, $on, $alias, &$owner)
    {
        $this->setJoin($join, $on, 'FULL OUTER', $alias, $owner);
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
     * @return 
     */
    public function setJoin($join, $on, $mode, $alias, &$owner)
    {
        if (is_array($join)) {
            $joinKeys = array_keys($join);
            $joinVals = array_values($join);
            $tblName = $joinVals[0];
            $expr = " {$joinKeys[0]} AS $tblName ";
        } else
            $tblName = $expr = $join;

        if (is_array($on))
            $onCond = " $alias.{$on[0]}=$tblName.{$on[1]} ";
        else
            $onCond = " $on ";
        
        $owner->getDb()->setJoin($expr, $onCond, $mode);

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
            $relation = $joinKeys[0];
        } else
            $relation = $join;
            
        return $relation;
    }
}
