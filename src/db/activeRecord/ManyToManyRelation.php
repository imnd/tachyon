<?php
namespace tachyon\db\activeRecord;

use tachyon\db\activeRecord\Join,
    tachyon\db\Alias;

/**
 * Класс реализующий связь "многие ко многим" между моделями
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2020 IMND
 */
class ManytomanyRelation extends Relation
{
    /**
     * @var \tachyon\db\Alias $Alias
     */
    protected $alias;
    /**
     * @var \tachyon\db\activeRecord\Join $join
     */
    protected $join;

    public function __construct(Alias $alias, Join $join)
    {
        $this->join = $join;
    }

    /**
     * для алиасинга полей расшивочной таблицы
     */
    const LINK_TBL_SUFF = '_lnk';
    
    public function joinWith($owner)
    {
        $linkParams = $this->params[2];
        $linkModelName = "\\models\\{$linkParams[0]}";
        $linkTableName = $linkModelName::getTableName();
        $linkTableAlias = $linkTableName . self::LINK_TBL_SUFF;
        $tableAliases[$linkTableName] = $linkTableAlias;
        $linkRelativeKey = $linkParams[1];
        $linkThisKey = $linkParams[2];
        $this->join->leftJoin("$linkTableName AS $linkTableAlias", " $linkTableAlias.$linkRelativeKey={$owner->getTableName()}.{$owner->getPkName()}", $this->getTableAlias(), $owner);
        $this->join->leftJoin("{$this->tableName} AS {$this->tableAlias}", " $linkTableAlias.$linkThisKey={$this->tableAlias}.{$this->pkName}", $this->getTableAlias(), $owner);
        if (isset($linkParams[3])) {
            $owner->setSelectFields(array_merge($owner->getSelectFields(), $this->alias->aliasFields($linkParams[3], $linkTableName, self::LINK_TBL_SUFF)));
        }
    }

    /**
     * убираем суффиксы у ключей
     */
    public function trimSuffixes($with)
    {
        parent::trimSuffixes($with);
        
        $this->values = $this->alias->trimSuffixes($this->values, self::LINK_TBL_SUFF, $with);
    }
    
    /**
     * выбираем значения внешних полей
     */
    public function setValues($itemArray)
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
        $retItemWith = $retItem->$with;
        if (is_null($retItem->$with)) {
            $retItem->$with = array($this->model);
        } else {
            $retItem->$with = array_merge($retItem->$with, array($this->model));
        }
        return $retItem;
    }
}