<?php
namespace tachyon\db\relations;

/**
 * class ManytomanyRelation
 * Класс реализующий связь "многие ко многим" между моделями
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
class ManytomanyRelation extends Relation
{
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
        $join = $this->getJoin();
        $join->leftJoin("$linkTableName AS $linkTableAlias", " $linkTableAlias.$linkRelativeKey={$owner->getTableName()}.{$owner->getPrimKey()}", $this->getTableAlias(), $owner);
        $join->leftJoin("{$this->tableName} AS {$this->tableAlias}", " $linkTableAlias.$linkThisKey={$this->tableAlias}.{$this->primKey}", $this->getTableAlias(), $owner);
        if (isset($linkParams[3])) {
            $owner->setSelectFields(array_merge($owner->getSelectFields(), $this->getAlias()->aliasFields($linkParams[3], $linkTableName, self::LINK_TBL_SUFF)));
        }
    }

    /**
     * убираем суффиксы у ключей
     */
    public function trimSuffixes($with)
    {
        parent::trimSuffixes($with);
        
        $this->values = $this->getAlias()->trimSuffixes($this->values, self::LINK_TBL_SUFF, $with);
    }
    
    /**
     * выбираем значения внешних полей
     */
    public function setValues($itemArray)
    {
        $relationKeys = array_flip($this->params[3]);
        $relationKeys = $this->getAlias()->appendSuffixToKeys($relationKeys, $this->aliasSuffix);
        $linkParams = $this->params[2];
        if (isset($linkParams[3])) {
            $addRelationKeys = $this->getAlias()->appendSuffixToKeys(array_flip($linkParams[3]), self::LINK_TBL_SUFF);
            $relationKeys = array_merge($relationKeys, $addRelationKeys);
        }
        $this->values = array_intersect_key($itemArray, $relationKeys);
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