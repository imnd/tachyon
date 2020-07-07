<?php
namespace tachyon\db\activeRecord;

use tachyon\{
    components\Message,
    db\Alias,
    dic\Container
};

/**
 * class Relation
 * Класс реализующий связи между моделями
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
abstract class Relation
{
    /**
     * @var Message $msg
     */
    protected $msg;
    /**
     * @var Alias $Alias
     */
    protected $alias;

    protected $tableName;
    protected $fields;
    protected $values;
    protected $tableAlias;
    protected $params;
    protected $pkName;
    protected $modelName;
    protected $model;
    protected $aliasSuffix;
    protected $linkKey;
    protected $relationKeys;
    
    public function __construct(Message $msg, Alias $alias, array $params = array())
    {
        $this->msg = $msg;
        $this->alias = $alias;

        $this->modelName = $params['modelName'];
        $model = (new Container)->get($this->modelName);
        $this->tableName = $model::getTableName();
        $this->pkName = $model->getPkName();
        $this->linkKey = $params['linkKey'];
        $this->aliasSuffix = "_{$params['type']}";
        $this->tableAlias = $this->tableName . $this->aliasSuffix;
        $this->relationKeys = $this->alias->appendSuffixToKeys(array_flip($params['relationKeys']), $this->aliasSuffix);
        $this->fields = $this->alias->aliasFields($params['relationKeys'], $this->tableName, $this->aliasSuffix);
    }

    /**
     * trimSuffixes
     * Убираем суффиксы у ключей
     * @param $with array
     */
    public function trimSuffixes($with='')
    {
        $this->values = $this->alias->trimSuffixes($this->values, $this->aliasSuffix, $with);
    }
    
    /**
     * attachWithObject
     * Приделываем $with к соотв. эл-ту массива $retItems
     * 
     * @param $retItem array
     * @param $with array
     */
    abstract public function attachWithObject($retItem, $with);

    /**
     * Формируем условие join
     */
    abstract public function joinWith($owner);

    # Геттеры и сеттеры

    public function setModelAttrs()
    {
        $this->model = $this->get($this->modelName);
        $this->model->setAttributes($this->values);
        return $this;
    }

    public function getTableName()
    {
        return $this->tableName;
    }

    public function setTableName($tableName)
    {
        $this->tableName = $tableName;
        return $this;
    }

    public function getFields()
    {
        return $this->fields;
    }

    public function setFields($fields)
    {
        $this->fields = $fields;
        return $this;
    }

    public function getTableAlias()
    {
        return $this->tableAlias;
    }

    /**
     * Выбираем значения внешних полей
     * @param $itemArray array
     */
    public function setValues($itemArray)
    {
        $this->values = array_intersect_key($itemArray, $this->relationKeys);
        return $this;
    }

    public function getValues()
    {
        return $this->values;
    }

    public function getDb()
    {
        return $this->owner->getDb();
    }
}