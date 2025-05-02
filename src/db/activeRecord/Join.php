<?php
namespace tachyon\db\activeRecord;

use tachyon\interfaces\HasOwnerInterface;
use tachyon\traits\HasOwner;

/**
 * @author imndsu@gmail.com
 */
class Join implements HasOwnerInterface
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
     * set table join
     *
     * @param $join array | string
     *      string - table name
     *      array  - table [name => alias]
     * @param $on array | string by what fields join [pk => fk]
     * @param $type string join type
     * @param $alias string alias of the main request table
     *
     * @return Join
     */
    public function setJoin(
        array | string $join,
        array | string $on,
        string $type,
        string $alias
    ): Join {
        if (is_array($join)) {
            $joinKeys = array_keys($join);
            $joinVals = array_values($join);
            $tblName = $joinVals[0];
            $expr = " {$joinKeys[0]} AS $tblName ";
        } else {
            $tblName = $expr = $join;
        }
        $onCond = is_array($on) ? " $alias.{$on[0]}=$tblName.{$on[1]} " : " $on ";

        $this->owner->getDb()->addJoin($expr, $onCond, $type);

        return $this;
    }

    public function getRelationName(array | string $join): string
    {
        if (is_array($join)) {
            $joinKeys = array_keys($join);
            return $joinKeys[0];
        }
        return $join;
    }
}
