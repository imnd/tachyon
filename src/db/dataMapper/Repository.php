<?php

namespace tachyon\db\dataMapper;

use ErrorException,
    Iterator,
    tachyon\db\Terms,
    tachyon\traits\ClassName,
    tachyon\exceptions\DBALException;

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
    protected Persistence $persistence;

    /**
     * Имя таблицы БД
     *
     * @var string
     */
    protected string $tableName = '';
    /**
     * Класс сущности
     *
     * @var Entity
     */
    protected Entity $entity;
    /**
     * Массив сущностей
     *
     * @var array
     */
    protected array $collection = [];

    public function __construct(Persistence $persistence)
    {
        $this->persistence = $persistence;
        $this->persistence->setOwner($this);
        if (empty($this->tableName)) {
            $tableNameArr = preg_split('/(?=[A-Z])/', str_replace('Repository', '', static::class));
            array_shift($tableNameArr);
            $this->tableName = strtolower(implode('_', $tableNameArr));
        }
    }

    /**
     * @return string
     */
    public function getTableName(): string
    {
        return $this->tableName;
    }

    /**
     * @inheritdoc
     */
    public function create($mark = true): Entity
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
    public function setSearchConditions(array $conditions = []): self
    {
        $this->where($conditions);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function findOne(array $where = []): ?Entity
    {
        if (!$entity = $this->findOneRaw($where)) {
            return null;
        }
        return $this->convertData($entity);
    }

    /**
     * @param array $data
     *
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
    public function findOneRaw(array $where = []): ?array
    {
        return $this
            ->persistence
            ->from($this->tableName)
            ->findOne($where);
    }

    /**
     * @inheritdoc
     */
    public function findAllRaw(array $where = [], array $sort = []): array
    {
        return $this->persistence
            ->from($this->tableName)
            ->findAll($where, $sort);
    }

    /**
     * @inheritdoc
     */
    public function findAll(array $where = [], array $sort = []): Iterator
    {
        $arrayData = $this->findAllRaw($where, $sort);
        return $this->convertArrayData($arrayData);
    }

    /**
     * @param array $arrayData
     *
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
     *
     * @return Entity
     * @throws DBALException
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
     *
     * @return Entity
     * @throws DBALException
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
     *
     * @return self
     */
    public function where($where): self
    {
        $this->persistence->setWhere($where);
        return $this;
    }

    /**
     * Устанавливает поля выборки.
     *
     * @param array $fields
     *
     * @return self
     */
    public function select($fields): self
    {
        $this->persistence->select((array)$fields);
        return $this;
    }

    /**
     * Устанавливает условия сортировки для хранилища
     *
     * @param array $attrs
     *
     * @return self
     * @throws ErrorException
     */
    public function setSort($attrs): self
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
     *
     * @return void
     * @throws ErrorException
     */
    public function addSortBy($field, $order): void
    {
        $this->persistence->orderBy($field, $order);
    }

    /**
     * truncates table
     *
     * @return void
     * @throws DBALException
     */
    public function clear(): void
    {
        $this->persistence->clear();
    }
}
