<?php
namespace tachyon\db\dataMapper;

use Iterator,
    tachyon\db\dataMapper\Entity,
    tachyon\db\dataMapper\Persistence,
    tachyon\db\Terms,
    tachyon\helpers\StringHelper,
    tachyon\traits\ClassName
;

/**
 * EntityManager является центральной точкой доступа к функциональности DataMapper ORM.
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2019 IMND
 */
abstract class Repository
{
    use ClassName;

    /**
     * @var \tachyon\db\dataMapper\Persistence
     */
    protected $persistence;
    /**
     * @var \tachyon\Terms $terms
     */
    protected $terms;

    // ВЫПИЛИТЬ или перенести вниз по иерархии
    
    /**
     * Имя таблицы БД
     * @var string
     */
    protected $tableName;
    /**
     * Имя класса сущности
     * @var string
     */
    protected $entityName;
    /**
     * Массив сущностей
     * @var array
     */
    protected $collection;

    public function __construct(Persistence $persistence, Terms $terms = null)
    {
        $this->persistence = $persistence;
        $this->persistence->setOwner($this);
        $this->terms = $terms ?? new Terms;

        if (is_null($this->tableName)) {
            $tableNameArr = preg_split('/(?=[A-Z])/', str_replace('Repository', '', get_called_class()));
            array_shift($tableNameArr);
            $this->tableName = strtolower(implode('_', $tableNameArr)) . 's';
        }
        if (is_null($this->entityName)) {
            $this->entityName = lcfirst(str_replace('Repository', '', $this->getClassName()));
        }
    }

    /**
     * @return string
     */
    public function getTableName()
    {
        return $this->tableName;
    }

    /**
     * @inheritdoc
     */
    public function create($mark = true)
    {
        $entity = clone($this->{$this->entityName});
        if ($mark) {
            $entity->markNew();
        }
        return $entity;
    }

    /**
     * @inheritdoc
     */
    public function setSearchConditions($conditions = array()): Repository
    {
        $this->where($conditions);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function findOneRaw(array $where = array(), array $sort = array()): array
    {
        return $this->persistence
            ->from($this->tableName)
            ->findOne($where, $sort);
    }

    /**
     * @inheritdoc
     */
    public function findAllRaw(array $where = array(), array $sort = array()): array
    {
        return $this->persistence
            ->from($this->tableName)
            ->findAll($where, $sort);
    }

    /**
     * @inheritdoc
     */
    public function findAll(array $where = array(), array $sort = array()): Iterator
    {
        $arrayData = $this->findAllRaw($where, $sort);
        return $this->convertArrayData($arrayData);
    }

    /**
     * @param array $arrayData
     * @return Iterator
     */
    protected function convertArrayData($arrayData): Iterator
    {
        foreach ($arrayData as $data) {
            $entity = $this->{$this->entityName}->fromState($data);
            yield $this->collection[$entity->getPk()] = $entity;
        }
    }

    /**
     * Получить сущность по первичному ключу и поместить в $this->collection.
     * 
     * @param int $pk
     * @return Entity
     */
    public function findByPk($pk)
    {
        if (!isset($this->collection[$pk])) {
            $this->collection[$pk] = $this->getByPk($pk);
        }
        return $this->collection[$pk];
    }

    /**
     * Получить сущность из БД по первичному ключу.
     * 
     * @param int $pk
     * @return Entity
     */
    protected function getByPk($pk): Entity
    {
        $data = $this
            ->persistence
            ->setTableName($this->tableName)
            ->findByPk($pk);

        return $this->{$this->entityName}->fromState($data);
    }

    /**
     * Устанавливает условие выборки.
     * 
     * @param array $where
     * @return void
     */
    public function where($where)
    {
        $this->persistence->setWhere($where);
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
        $this->persistence->orderBy($field, $order);
    }

    /**
     * truncates table
     * @return void
     */
    public function clear()
    {
        $this->persistence->clear();
    }
}
