<?php
namespace tachyon\db\dataMapper;

use tachyon\db\dbal\DbFactory;

class Persistence
{
    use \tachyon\traits\HasOwner;

    /**
     * Имя текущей (главной) таблицы запроса
     */
    protected $tableName;
    /**
     * Алиас текущей (главной) таблицы запроса
     */
    protected $tableAlias = 't';
    /**
     * Поля выборки.
     * выпилить. переместить в DB
     */
    protected $select = array();

    /**
     * @var \tachyon\db\dbal\Db
     */
    protected $db;

    /**
     * @return void
     */
    public function __construct(DbFactory $dbFactory)
    {
        $this->db = $dbFactory->getDb();
    }

    /**
     * Находит все записи по условию $where, отсортированные по $sort
     * 
     * @param array $where
     * @param array $fields
     * @param array $sort
     * @param string $tableName
     */
    public function findAll(array $where = [], array $sortBy = [], array $fields = [], $tableName = null): array
    {
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
     * Делает запрос $query к БД и извлекает результаты в виде массива.
     * @param string $query
     * @return array
     */
    public function queryAll(string $query)
    {
        return $this->db->queryAll($query);
    }

    /**
     * Находит все записи по условию $where, отсортированные по $sort
     * 
     * @param array $where
     * @param array $fields
     * @param string $tableName
     */
    public function findOne(array $where = [], array $fields = [], $tableName = null): array
    {
        if (!is_null($tableName)) {
            $this->tableName = $tableName;
        }
        $this->_alias();
        return $this->db->selectOne($this->tableName, $where, array_merge($this->select, $fields));
    }

    /**
     * @return void
     */
    private function _alias()
    {
        if (!is_null($this->tableAlias)) {
            $this->tableName .= " AS {$this->tableAlias}";
        }
    }

    /**
     * Находит запись по первичному ключу
     * 
     * return mixed;
     */
    public function findByPk($pk, $tableName = null)
    {
        if (!is_null($tableName)) {
            $this->tableName = $tableName;
        }
        return $this->db->selectOne($this->tableName, ['id' => $pk]);
    }

    /**
     * Обновляет запись по первичному ключу
     * 
     * @return boolean
     */
    public function updateByPk($pk, array $fieldValues, $tableName = null)
    {
        if (!is_null($tableName)) {
            $this->tableName = $tableName;
        }
        return $this->db->update($this->tableName, $fieldValues, ['id' => $pk]);
    }

    /**
     * Сохраняет запись в хранилище
     * 
     * @return boolean
     */
    public function insert(array $fieldValues, $tableName = null)
    {
        if (!is_null($tableName)) {
            $this->tableName = $tableName;
        }
        return $this->db->insert($this->tableName, $fieldValues);
    }

    /**
     * Удаляет запись из хранилища
     *
     * @param mixed $pk
     * @return boolean
     */
    public function deleteByPk($pk, $tableName = null)
    {
        if (!is_null($tableName)) {
            $this->tableName = $tableName;
        }
        return $this->db->delete($this->tableName, ['id' => $pk]);
    }

    /**
     * @return void
     */
    public function beginTransaction()
    {
        $this->db->beginTransaction();
    }

    /**
     * @return void
     */
    public function endTransaction()
    {
        $this->db->endTransaction();
    }

    /**
     * Устанавливаем какие таблицы джойнить
     * 
     * @param array $with
     * @param array $on
     * @return Persistence
     */
    public function with(array $with, $on = array())
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
        $this->db->setJoin("$withTableName AS $withAlias", "$onPrimaryKey = $onForeignKey");

        return $this;
    }

    /**
     * Устанавливает условие выборки.
     * 
     * @param array $where
     * @return void
     */
    public function setWhere($where)
    {
        $this->db->setWhere($where);
    }

    /**
     * Устанавливает LIMIT.
     * 
     * @param string $limit
     * @return Persistence
     */
    public function limit($limit)
    {
        $this->db->setLimit($limit);
        return $this;
    }

    /**
     * Устанавливает поля сортировки.
     * 
     * @param string $field
     * @param string $order
     * @return Persistence
     */
    public function orderBy($field, $order)
    {
        $this->db->orderBy($field, $order);
        return $this;
    }

    /**
     * Устанавливает поля сортировки.
     * 
     * @param string $fields
     * @return Persistence
     */
    public function setOrderBy($fields)
    {
        $this->db->setOrderBy($fields);
        return $this;
    }

    /**
     * Устанавливает поля сортировки.
     * 
     * @param string $fields
     * @return Persistence
     */
    public function groupBy($fields)
    {
        $this->db->setGroupBy($fields);
        return $this;
    }

    /**
     * Устанавливает поля выборки.
     * 
     * @param string $fields
     * @return Persistence
     */
    public function select($fields)
    {
        $this->db->setFields((array)$fields);
        return $this;
    }

    /**
     * @param string $tableName
     * @return Persistence
     */
    public function setTableName($tableName): Persistence
    {
        $this->tableName = $tableName;
        return $this;
    }

    /**
     * @param string $tableName
     * @return Persistence
     */
    public function from($tableName): Persistence
    {
        $this->tableName = $tableName;
        return $this;
    }

    /**
     * Устанавливает алиас текущей (главной) таблицы запроса
     * 
     * @param string $alias
     * @return Persistence
     */
    public function asa($alias)
    {
        $this->tableAlias = $alias;
        return $this;
    }
}
