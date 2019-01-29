<?php
namespace tachyon\db\dataMapper;

use Iterator;
use tachyon\db\dataMapper\Entity;
use tachyon\helpers\StringHelper;

/**
 * EntityManager является центральной точкой доступа к функциональности DataMapper ORM.
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2019 IMND
 */
abstract class Repository extends \tachyon\Component
{
    use \tachyon\dic\Persistence;

    /** @var string */
    protected $tableName;
    /**
     * Имя класса сущности
     */
    protected $entityName;
    /**
     * Массив сущностей
     */
    protected $collection;

    public function initialize()
    {
        if (is_null($this->entityName)) {
            $this->entityName = lcfirst( str_replace('Repository', '', StringHelper::getShortClassName(get_called_class())) );
        }
    }

    /**
     * @param string $tableName
     * @return void
     */
    public function getTableName()
    {
        return $this->tableName;
    }

    /**
     * Добавляет в коллекцию сущность $entity
     * 
     * @param Entity $entity
     * @return void
     */
    public function add($entity)
    {
        $this->collection[$entity->getPk()] = $entity;
    }

    /**
     * Получить все сущности по условию $condition
     * 
     * @return array
     */
    public function findAll(array $condition = array()): Iterator
    {
        $arrayData = $this->persistence->findAll($condition);
        foreach ($arrayData as $data) {
            $entity = $this->{$this->entityName}->fromState($data);
            yield $this->collection[$entity->getPk()] = $entity;
        }
    }

    /**
     * Получить запись по первичному ключу
     * @return Entity
     */
    public function findByPk($pk)//: ?Entity
    {
        if (!isset($this->collection[$pk])) {
            $data = $this->persistence->findByPk($pk);
            $this->collection[$pk] = $this->{$this->entityName}->fromState($data);
        }
        return $this->collection[$pk];
    }

    /**
     * Сохраняет в хранилище измененную сущность
     * 
     * @param Entity $entity
     * @return boolean
     */
    public function update(Entity $entity)
    {
        return $this->persistence->updateByPk($entity->getPk(), $entity->getAttributes());
    }

    /**
     * Вставляет в хранилище новую сущность
     *
     * @param Entity $entity
     * @return boolean
     */
    public function insert(Entity $entity)
    {
        return $this->persistence->insert($entity->getAttributes());
    }

    /**
     * Удаляет сущность из хранилища
     * 
     * @param Entity $entity
     * @return boolean
     */
    public function delete(Entity $entity)
    {
        return $this->persistence->delete($entity->getPk());
    }
}
