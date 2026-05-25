<?php

namespace tachyon\db\dataMapper;

use ErrorException;
use tachyon\db\dbal\{
    Db, DbFactory
};
use ReflectionException;
use tachyon\db\Query;
use tachyon\exceptions\ContainerException;
use tachyon\traits\HasOwner;
use tachyon\exceptions\DBALException;

class Persistence
{
    use HasOwner;

    /**
     * Name of the current (main) query table
     */
    protected ?string $tableName = null;
    /**
     * Alias of the current (main) query table
     */
    protected string $tableAlias = 't';
    /**
     * Selection fields.
     * TODO: remove, move to DB
     */
    protected array $select = [];

    protected Db $db;

    /**
     * @throws DBALException
     * @throws ReflectionException
     * @throws ContainerException
     */
    public function __construct(
        protected Query $query,
        DbFactory $dbFactory
    ) {
        $this->db = $dbFactory->getDb();
    }

    /**
     * Finds all records by condition $where sorted by $sort
     * @throws DBALException
     */
    public function findAll(
        array $where = [],
        array $sortBy = [],
        array $fields = [],
        string $tableName = null
    ): array {
        $actualTableName = $tableName ?? $this->tableName;
        if (!is_null($this->tableAlias)) {
            $actualTableName .= " AS {$this->tableAlias}";
        }
        if (!empty($sortBy)) {
            foreach ($sortBy as $fieldName => $order) {
                $this->query->orderBy($fieldName, $order);
            }
        }
        return $this->db->select(
            $this->query,
            $actualTableName,
            $where,
            array_merge($this->select, $fields)
        );
    }

    /**
     * Finds all records by condition $where sorted by $sort
     * @throws DBALException
     */
    public function findOne(array $where = [], array $fields = [], string $tableName = null): ?array
    {
        $actualTableName = $tableName ?? $this->tableName;
        if (!is_null($this->tableAlias)) {
            $actualTableName .= " AS {$this->tableAlias}";
        }
        return $this->db->selectOne(
            $this->query,
            $actualTableName,
            $where,
            array_merge($this->select, $fields)
        );
    }

    /**
     * Finds record by primary key
     * @throws DBALException
     */
    public function findByPk(string|int $id, string $tableName = null): mixed
    {
        $actualTableName = $tableName ?? $this->tableName;
        return $this->db->selectOne($this->query, $actualTableName, ['id' => $id]);
    }

    /**
     * Updates record by primary key
     * @throws DBALException
     */
    public function updateByPk(string|int $id, array $fieldValues, string $tableName = null): bool
    {
        $actualTableName = $tableName ?? $this->tableName;
        return $this->db->update($this->query, $actualTableName, $fieldValues, ['id' => $id]);
    }

    /**
     * Saves record to storage
     * @throws DBALException
     */
    public function insert(array $fieldValues, string $tableName = null): mixed
    {
        $actualTableName = $tableName ?? $this->tableName;
        return $this->db->insert($this->query, $actualTableName, $fieldValues);
    }

    /**
     * Deletes record from storage
     * @throws DBALException
     */
    public function deleteByPk(mixed $id, string $tableName = null): bool
    {
        $actualTableName = $tableName ?? $this->tableName;
        return $this->db->delete($this->query, $actualTableName, ['id' => $id]);
    }

    /**
     * Truncates table $this->tableName
     * @throws DBALException
     */
    public function clear(): void
    {
        $this->db->truncate($this->tableName);
    }

    /**
     * @throws DBALException
     */
    public function beginTransaction(): void
    {
        $this->db->beginTransaction();
    }

    public function endTransaction(): void
    {
        $this->db->endTransaction();
    }

    /**
     * Which tables to join
     */
    public function with(array $with, array|string $on = []): static
    {
        $withAlias = current($with);
        $withTableName = key($with);
        if (is_array($on)) {
            $onForeignKey = key($on);
            $onPrimaryKey = current($on);
        } else {
            $onForeignKey = $onPrimaryKey = $on;
        }
        $onForeignKey = "{$this->tableAlias}.$onForeignKey";
        $onPrimaryKey = "$withAlias.$onPrimaryKey";
        if (!in_array($onForeignKey, $this->select)) {
            $this->select[] = $onForeignKey;
        }
        if (!in_array($onPrimaryKey, $this->select)) {
            $this->select[] = $onPrimaryKey;
        }
        $this->query->addJoin(
            "$withTableName AS $withAlias",
            "$onPrimaryKey = $onForeignKey"
        );
        return $this;
    }

    /**
     * Sets the selection condition.
     */
    public function setWhere(array $where): static
    {
        $this->query->setWhere($where);
        return $this;
    }

    /**
     * Sets LIMIT
     */
    public function limit(string $limit): static
    {
        $this->query->setLimit($limit);
        return $this;
    }

    /**
     * Sets sorting fields
     */
    public function orderBy(array|string $field, string $order = null): static
    {
        if (is_null($order)) {
            if (!is_array($field)) {
                throw new ErrorException('Неправильный формат аргументов в методе Persistence::orderBy()');
            }
            $order = current($field);
            $field = key($field);
        }
        $this->query->orderBy($field, $order);
        return $this;
    }

    /**
     * Sets sorting fields
     */
    public function setOrderBy(string $fieldName): static
    {
        $this->query->setOrderBy($fieldName);
        return $this;
    }

    /**
     * Sets sorting fields
     */
    public function groupBy(string $fieldName): static
    {
        $this->query->setGroupBy($fieldName);
        return $this;
    }

    /**
     * Sets selection fields
     */
    public function select(array | string $fields): static
    {
        $this->query->setFields((array)$fields);
        return $this;
    }

    public function setTableName(string $tableName): static
    {
        $this->tableName = $tableName;
        return $this;
    }

    public function from(string $tableName): static
    {
        $this->tableName = $tableName;
        return $this;
    }

    /**
     * Sets alias of the current (main) query table
     */
    public function asa(string $alias): static
    {
        $this->tableAlias = $alias;
        return $this;
    }
}
