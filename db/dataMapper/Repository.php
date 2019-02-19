<?php
namespace tachyon\db\dataMapper;

use Iterator,
    tachyon\db\dataMapper\Entity,
    tachyon\helpers\StringHelper;

/**
 * EntityManager является центральной точкой доступа к функциональности DataMapper ORM.
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2019 IMND
 */
abstract class Repository extends \tachyon\Component
{
    use \tachyon\dic\Persistence,
        \tachyon\dic\Terms;

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
    /**
     * условия для поиска
     */
    protected $where = array();
    /**
     * сортировка
     */
    protected $sortBy = array();

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
     * Устанавливает условия поиска для хранилища
     * 
     * @param array $conditions условия поиска
     * @return Repository
     */
    public function setSearchConditions($conditions=array()): Repository
    {
        return $this;
    }

    /**
     * Устанавливает условия сортировки для хранилища
     * 
     * @param array $attrs
     * @return Repository
     */
    public function setSort($attrs)
    {
        if (isset($attrs['order'])) {
            $this->addSortBy($attrs['field'], $attrs['order']);
        }
        return $this;
    }

    /**
     * Добавляет условия сортировки для хранилища к уже существующим
     * 
     * @param string $field
     * @param string $order
     * @return void
     */
    public function addSortBy($field, $order)
    {
        $this->sortBy = array_merge($this->sortBy, [$field => $order]);
    }

    /**
     * Создать новую сущность
     * @return Entity
     */
    public function create()//: ?Entity
    {
        $entity = clone($this->{$this->entityName});
        $entity->markNew();
        return $entity;
    }

    /**
     * Получить все сущности по условию $condition
     * 
     * @return array
     */
    public function findAll(array $conditions = array(), array $sort = array()): Iterator
    {
        $arrayData = $this->persistence->findAll(array_merge($this->where, $conditions), $sort);
        $this->where = $this->sortBy = array();
        foreach ($arrayData as $data) {
            $entity = $this->{$this->entityName}->fromState($data);
            yield $this->collection[$entity->getPk()] = $entity;
        }
    }

    /**
     * Получить сущность по первичному ключу
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
