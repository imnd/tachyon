<?php
namespace tachyon\db\dataMapper;

use Iterator,
    tachyon\db\dataMapper\Entity,
    tachyon\db\dataMapper\Persistence,
    tachyon\db\Terms,
    tachyon\traits\ClassName,
    tachyon\db\dataMapper\RepositoryInterface
;

/**
 * EntityManager является центральной точкой доступа к функциональности DataMapper ORM.
 *
 * @author Андрей Сердюк
 * @copyright (c) 2020 IMND
 */
abstract class Repository implements RepositoryInterface
{
    use ClassName, Terms;

    /**
     * @var Persistence
     */
    protected $persistence;

    /**
     * Имя таблицы БД
     * @var string
     */
    protected $tableName;
    /**
     * Имя класса сущности
     * @var string
     */
    protected $entity;
    /**
     * Массив сущностей
     * @var array
     */
    protected $collection = [];

    public function __construct(Persistence $persistence)
    {
        $this->persistence = $persistence;
        $this->persistence->setOwner($this);

        if (is_null($this->tableName)) {
            $tableNameArr = preg_split('/(?=[A-Z])/', str_replace('Repository', '', get_called_class()));
            array_shift($tableNameArr);
            $this->tableName = strtolower(implode('_', $tableNameArr));
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
    public function create($mark = true): ?Entity
    {
        $entity = clone($this->entity);
        if ($mark) {
            $entity->markNew();
        }
        return $entity;
    }

    /**
     * @inheritdoc
     */
    public function setSearchConditions(array $conditions = array()): Repository
    {
        $this->where($conditions);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function findOne(array $where = array()): ?Entity
    {
        if (!$entity = $this->findOneRaw($where)) {
            return null;
        }
        return $this->convertData($entity);
    }

    /**
     * @param array $data
     * @return array
     */
    protected function convertData($data): Entity
    {
        $entity = $this->entity->fromState($data);

        return $this->collection[$entity->getPk()] = $entity;
    }

    /**
     * @inheritdoc
     */
    public function findOneRaw(array $where = array()): ?Entity
    {
        return $this->persistence
            ->from($this->tableName)
            ->findOne($where);
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
            $entity = $this->entity->fromState($data);
            yield $this->collection[$entity->getPk()] = $entity;
        }
    }

    /**
     * Получить сущность по первичному ключу и поместить в $this->collection.
     *
     * @param int $pk
     * @return Entity
     */
    public function findByPk($pk): ?Entity
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
    protected function getByPk($pk): ?Entity
    {
        if ($data = $this
            ->persistence
            ->setTableName($this->tableName)
            ->findByPk($pk)) {
            return $this->entity->fromState($data);
        }
        return null;
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
