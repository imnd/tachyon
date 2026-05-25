<?php

namespace tachyon\db\dataMapper;

use Iterator;

/**
 * EntityManager is the central entry point to the DataMapper ORM functionality.
 *
 * @author imndsu@gmail.com
 */
abstract class Repository implements RepositoryInterface
{
    protected Persistence $persistence;
    /**
     * DB table name
     */
    protected string $tableName = '';
    protected string $tableAlias = 't';
    /**
     * Entity class
     */
    protected Entity $entity;
    /**
     * Array of entities
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

    public function getTableName(): string
    {
        return $this->tableName;
    }

    public function create(bool $markNew = true): ?Entity
    {
        $entity = clone($this->entity);
        if ($markNew) {
            $entity->markNew();
        }
        return $entity;
    }

    public function setSearchConditions(array $conditions = []): self
    {
        $this->where(array_diff_key($conditions, ['order' => null, 'order-by' => null]));

        return $this;
    }

    public function findOne(array $where = []): ?Entity
    {
        if (!$entity = $this->findOneRaw($where)) {
            return null;
        }
        return $this->convertData($entity);
    }

    protected function convertData(array $data): Entity
    {
        $entity = $this->entity->fromState($data);
        $entity->setIsNew(false);
        return $this->collection[$entity->getPk()] = $entity;
    }

    public function findOneRaw(array $where = []): ?array
    {
        return $this
            ->persistence
            ->from($this->tableName)
            ->findOne($where);
    }

    public function findAllRaw(array $where = [], array $sort = []): array
    {
        return $this->persistence
            ->from($this->tableName)
            ->findAll($where, $sort);
    }

    public function findAll(array $where = [], array $sort = []): Iterator
    {
        $arrayData = $this->findAllRaw($where, $sort);
        return $this->convertArrayData($arrayData);
    }

    protected function convertArrayData(array $arrayData): Iterator
    {
        foreach ($arrayData as $data) {
            $entity = $this->entity->fromState($data);
            $entity->setIsNew(false);
            yield $this->collection[$entity->getPk()] = $entity;
        }
    }

    /**
     * Get entity by primary key and place it in $this->collection.
     */
    public function findByPk(mixed $pk): ?Entity
    {
        if (!isset($this->collection[$pk])) {
            $this->collection[$pk] = $this->getByPk($pk);
        }
        return $this->collection[$pk];
    }

    /**
     * Get entity from DB by primary key.
     */
    protected function getByPk(int $pk): ?Entity
    {
        if ($data = $this
            ->persistence
            ->setTableName($this->tableName)
            ->findByPk($pk)) {
            $entity = $this->entity->fromState($data);
            $entity->setIsNew(false);
            return $entity;
        }
        return null;
    }

    /**
     * Sets the selection condition.
     */
    public function where(array $where): self
    {
        $this->persistence->setWhere($where);
        return $this;
    }

    /**
     * Sets selection fields.
     */
    public function select(mixed $fields): self
    {
        $this->persistence->select((array)$fields);
        return $this;
    }

    /**
     * Sets sorting conditions for storage
     */
    public function setSort(array $attrs): self
    {
        if ($orderBy = $attrs['order-by'] ?? null) {
            $this->addSortBy($orderBy, $attrs['order'] ?? 'ASC');
        }
        return $this;
    }

    /**
     * Adds sorting conditions for storage to existing ones
     */
    public function addSortBy(string $orderBy, string $order): void
    {
        $this->persistence->orderBy($orderBy, $order);
    }

    /**
     * truncates table
     */
    public function clear(): void
    {
        $this->persistence->clear();
    }
}
