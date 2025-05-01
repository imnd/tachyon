<?php

namespace tachyon\db\dataMapper;

use ErrorException;
use tachyon\db\dbal\{
    Db, DbFactory
};
use ReflectionException;
use tachyon\exceptions\ContainerException;
use tachyon\traits\HasOwner;
use tachyon\exceptions\DBALException;

class Persistence
{
    use HasOwner;

    /**
     * Имя текущей (главной) таблицы запроса
     */
    protected ?string $tableName = null;
    /**
     * Алиас текущей (главной) таблицы запроса
     */
    protected string $tableAlias = 't';
    /**
     * Поля выборки.
     * TODO: выпилить. переместить в DB
     */
    protected array $select = [];

    protected Db $db;

    /**
     * @throws DBALException
     * @throws ReflectionException
     * @throws ContainerException
     */
    public function __construct(DbFactory $dbFactory)
    {
        $this->db = $dbFactory->getDb();
    }

    /**
     * Находит все записи по условию $where, отсортированные по $sort
     */
    public function findAll(
        array $where = [],
        array $sortBy = [],
        array $fields = [],
        string $tableName = null
    ): array {
        if (!is_null($tableName)) {
            $this->tableName = $tableName;
        }
        $this->_alias();
        if (!empty($sortBy)) {
            foreach ($sortBy as $fieldName => $order) {
                $this->db->orderBy($fieldName, $order);
            }
        }
        return $this->db->select($this->tableName, $where, array_merge($this->select, $fields));
    }

    /**
     * Находит все записи по условию $where, отсортированные по $sort
     */
    public function findOne(array $where = [], array $fields = [], string $tableName = null): ?array
    {
        if (!is_null($tableName)) {
            $this->tableName = $tableName;
        }
        $this->_alias();
        return $this->db->selectOne($this->tableName, $where, array_merge($this->select, $fields));
    }

    private function _alias(): void
    {
        if (!is_null($this->tableAlias)) {
            $this->tableName .= " AS {$this->tableAlias}";
        }
    }

    /**
     * Находит запись по первичному ключу
     */
    public function findByPk(string|int $id, string $tableName = null): mixed
    {
        if (!is_null($tableName)) {
            $this->tableName = $tableName;
        }
        return $this->db->selectOne($this->tableName, ['id' => $id]);
    }

    /**
     * Обновляет запись по первичному ключу
     */
    public function updateByPk(string|int $id, array $fieldValues, string $tableName = null): bool
    {
        if (!is_null($tableName)) {
            $this->tableName = $tableName;
        }
        return $this->db->update($this->tableName, $fieldValues, ['id' => $id]);
    }

    /**
     * Сохраняет запись в хранилище
     */
    public function insert(array $fieldValues, string $tableName = null): mixed
    {
        if (!is_null($tableName)) {
            $this->tableName = $tableName;
        }
        return $this->db->insert($this->tableName, $fieldValues);
    }

    /**
     * Удаляет запись из хранилища
     */
    public function deleteByPk(mixed $id, string $tableName = null): bool
    {
        if (!is_null($tableName)) {
            $this->tableName = $tableName;
        }
        return $this->db->delete($this->tableName, ['id' => $id]);
    }

    /**
     * Truncates table $this->tableName
     */
    public function clear(): void
    {
        $this->db->truncate($this->tableName);
    }

    public function beginTransaction(): void
    {
        $this->db->beginTransaction();
    }

    public function endTransaction(): void
    {
        $this->db->endTransaction();
    }

    /**
     * Устанавливает какие таблицы джойнить
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
        $this->db->addJoin(
            "$withTableName AS $withAlias",
            "$onPrimaryKey = $onForeignKey"
        );
        return $this;
    }

    /**
     * Устанавливает условие выборки.
     */
    public function setWhere(array $where): static
    {
        $this->db->setWhere($where);
        return $this;
    }

    /**
     * Устанавливает LIMIT
     */
    public function limit(string $limit): static
    {
        $this->db->setLimit($limit);
        return $this;
    }

    /**
     * Устанавливает поля сортировки
     */
    public function orderBy(array|string $field, string $order = null): static
    {
        if (is_null($order)) {
            if (!is_array($field)) {
                throw new ErrorException('Неправильный формат аргументов в методе Persistence::orderBy()');
            }
            $order = key($field);
            $field = current($field);
        }
        $this->db->orderBy($field, $order);
        return $this;
    }

    /**
     * Устанавливает поля сортировки
     */
    public function setOrderBy(string $fieldName): static
    {
        $this->db->setOrderBy($fieldName);
        return $this;
    }

    /**
     * Устанавливает поля сортировки
     */
    public function groupBy(string $fieldName): static
    {
        $this->db->setGroupBy($fieldName);
        return $this;
    }

    /**
     * Устанавливает поля выборки
     */
    public function select(array | string $fields): static
    {
        $this->db->setFields((array)$fields);
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
     * Устанавливает алиас текущей (главной) таблицы запроса
     */
    public function asa(string $alias): static
    {
        $this->tableAlias = $alias;
        return $this;
    }
}
