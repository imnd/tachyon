<?php

namespace tachyon\db\dataMapper;

use Iterator;

/**
 * EntityManager является центральной точкой доступа к функциональности DataMapper ORM.
 *
 * @author imndsu@gmail.com
 */
abstract class Repository implements RepositoryInterface
{
    protected Persistence $persistence;
    /**
     * Имя таблицы БД
     */
    protected string $tableName = '';
    protected string $tableAlias = 't';
    /**
     * Класс сущности
     */
    protected Entity $entity;
    /**
     * Массив сущностей
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
     * Получить сущность по первичному ключу и поместить в $this->collection.
     */
    public function findByPk(mixed $pk): ?Entity
    {
        if (!isset($this->collection[$pk])) {
            $this->collection[$pk] = $this->getByPk($pk);
        }
        return $this->collection[$pk];
    }

    /**
     * Получить сущность из БД по первичному ключу.
     */
    protected function getByPk(int $pk): ?Entity
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
     */
    public function where(array $where): self
    {
        $this->persistence->setWhere($where);
        return $this;
    }

    /**
     * Устанавливает поля выборки.
     */
    public function select(mixed $fields): self
    {
        $this->persistence->select((array)$fields);
        return $this;
    }

    /**
     * Устанавливает условия сортировки для хранилища
     */
    public function setSort(array $attrs): self
    {
        if ($orderBy = $attrs['order-by'] ?? null) {
            $this->addSortBy($orderBy, $attrs['order'] ?? 'ASC');
        }
        return $this;
    }

    /**
     * Добавляет условия сортировки для хранилища к уже существующим
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
