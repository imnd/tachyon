<?php
namespace tachyon\db\activeRecord;

use tachyon\components\Message;
use tachyon\db\Alias;

/**
 * Class implementing "many to many" relation between models
 *
 * @author imndsu@gmail.com
 */
class ManyToManyRelation extends Relation
{
    /**
     * @var Alias $Alias
     */
    protected Alias $alias;
    /**
     * @var Join $join
     */
    protected Join $join;

    public function __construct(Message $msg, Alias $alias, Join $join)
    {
        parent::__construct($msg, $alias);

        $this->join = $join;
    }

    /**
     * for junction table fields aliasing
     */
    const LINK_TBL_SUFF = '_lnk';

    public function joinWith($owner): void
    {
        $linkParams = $this->params[2];
        $linkModelName = "\\models\\{$linkParams[0]}";
        $linkTableName = $linkModelName::getTableName();
        $linkTableAlias = $linkTableName . self::LINK_TBL_SUFF;
        $tableAliases[$linkTableName] = $linkTableAlias;
        $linkRelativeKey = $linkParams[1];
        $linkThisKey = $linkParams[2];
        $this->join->leftJoin(
            "$linkTableName AS $linkTableAlias",
            " $linkTableAlias.$linkRelativeKey={$owner->getTableName()}.{$owner->getPkName()}",
            $this->getTableAlias()
        );
        $this->join->leftJoin(
            "{$this->tableName} AS {$this->tableAlias}",
            " $linkTableAlias.$linkThisKey={$this->tableAlias}.{$this->pkName}",
            $this->getTableAlias()
        );
        if (isset($linkParams[3])) {
            $owner->setSelectFields(array_merge($owner->getSelectFields(), $this->alias->aliasFields($linkParams[3], $linkTableName, self::LINK_TBL_SUFF)));
        }
    }

    public function trimSuffixes($with = ''): void
    {
        parent::trimSuffixes($with);

        $this->values = $this->alias->trimSuffixes($this->values, self::LINK_TBL_SUFF, $with);
    }

    /**
     * select external fields values
     *
     * @param $itemArray
     *
     * @return Relation
     */
    public function setValues($itemArray): Relation
    {
        $relationKeys = array_flip($this->params[3]);
        $relationKeys = $this->alias->appendSuffixToKeys($relationKeys, $this->aliasSuffix);
        $linkParams = $this->params[2];
        if (isset($linkParams[3])) {
            $addRelationKeys = $this->alias->appendSuffixToKeys(array_flip($linkParams[3]), self::LINK_TBL_SUFF);
            $relationKeys = array_merge($relationKeys, $addRelationKeys);
        }
        $this->values = array_intersect_key($itemArray, $relationKeys);

        return $this;
    }

    public function attachWithObject($retItem, $with)
    {
        if (is_null($retItem->$with)) {
            $retItem->$with = array($this->model);
        } else {
            $retItem->$with = array_merge($retItem->$with, array($this->model));
        }
        return $retItem;
    }
}
