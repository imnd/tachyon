<?php
namespace tachyon\db\activeRecord;

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
        $this->join->leftJoin("$linkTableName AS $linkTableAlias", " $linkTableAlias.$linkRelativeKey={$owner->getTableName()}.{$owner->getPrimKey()}", $this->getTableAlias(), $owner);
        $this->join->leftJoin("{$this->tableName} AS {$this->tableAlias}", " $linkTableAlias.$linkThisKey={$this->tableAlias}.{$this->primKey}", $this->getTableAlias(), $owner);
        if (isset($linkParams[3])) {
            $owner->setSelectFields(array_merge($owner->getSelectFields(), $this->get('alias')->aliasFields($linkParams[3], $linkTableName, self::LINK_TBL_SUFF)));
        }
    }

    /**
     * убираем суффиксы у ключей
     */
    public function trimSuffixes($with)
    {
        parent::trimSuffixes($with);
        
        $this->values = $this->get('alias')->trimSuffixes($this->values, self::LINK_TBL_SUFF, $with);
    }
    
    /**
     * выбираем значения внешних полей
     */
    public function setValues($itemArray)
    {
        $relationKeys = array_flip($this->params[3]);
        $relationKeys = $this->get('alias')->appendSuffixToKeys($relationKeys, $this->aliasSuffix);
        $linkParams = $this->params[2];
        if (isset($linkParams[3])) {
            $addRelationKeys = $this->get('alias')->appendSuffixToKeys(array_flip($linkParams[3]), self::LINK_TBL_SUFF);
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