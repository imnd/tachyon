<?php
namespace tachyon\db\activeRecord;

use tachyon\{components\Message, db\Alias, dic\Container, exceptions\ContainerException};
use ReflectionException;

/**
 * class Relation
 * Класс реализующий связи между моделями
 *
 * @author Андрей Сердюк
 * @copyright (c) 2020 IMND
 */
abstract class Relation
{
    /**
     * @var Message $msg
     */
    protected Message $msg;
    /**
     * @var Alias $Alias
     */
    protected Alias $alias;

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

    /**
     * Relation constructor.
     *
     * @param Message $msg
     * @param Alias   $alias
     * @param array   $params
     *
     * @throws ReflectionException
     * @throws ContainerException
     */
    public function __construct(Message $msg, Alias $alias, array $params = array())
    {
        $this->msg = $msg;
        $this->alias = $alias;

        $this->modelName = $params['modelName'];
        $model = app()->get($this->modelName);
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
     *
     * @param string $with
     */
    public function trimSuffixes($with = ''): void
    {
        $this->values = $this->alias->trimSuffixes($this->values, $this->aliasSuffix, $with);
    }

    /**
     * attachWithObject
     * Приделываем $with к соотв. эл-ту объекта $retItem
     *
     * @param mixed  $retItem
     * @param string $with
     */
    abstract public function attachWithObject($retItem, $with);

    /**
     * Формируем условие join
     *
     * @param $owner
     */
    abstract public function joinWith($owner): void;

    # Геттеры и сеттеры

    public function setModelAttrs(): Relation
    {
        $this->model = $this->get($this->modelName);
        $this->model->setAttributes($this->values);
        return $this;
    }

    public function getTableName()
    {
        return $this->tableName;
    }

    public function setTableName($tableName): Relation
    {
        $this->tableName = $tableName;
        return $this;
    }

    public function getFields()
    {
        return $this->fields;
    }

    public function setFields($fields): Relation
    {
        $this->fields = $fields;
        return $this;
    }

    public function getTableAlias(): string
    {
        return $this->tableAlias;
    }

    /**
     * Выбираем значения внешних полей
     *
     * @param $itemArray array
     *
     * @return Relation
     */
    public function setValues($itemArray): Relation
    {
        $this->values = array_intersect_key($itemArray, $this->relationKeys);
        return $this;
    }

    public function getValues()
    {
        return $this->values;
    }
}
