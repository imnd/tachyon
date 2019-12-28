<?php
namespace tachyon\db\dbal;

use PDO,
    PDOException,
    tachyon\exceptions\DBALException,
    tachyon\components\Message;

/**
 * DBAL (на PDO)
 *
 * @author Андрей Сердюк
 * @copyright (c) 2019 IMND
 */
abstract class Db
{
    /**
     * параметры БД
     */
    protected $config;
    /**
     * соединение с БД (PDO)
     */
    protected $connection;
    /**
     * Компонент msg
     * @var tachyon\components\Message $msg
     */
    protected $msg;
    /**
     * Выводить ли анализ запросов в файл
     */
    protected $explain;
    /**
     * поля для выборки/вставки/обновления
     */
    protected $fields = array();
    /**
     * условия для выборки
     */
    protected $where = array();
    protected $join = '';
    protected $groupBy = '';
    protected $orderBy = array();
    protected $limit = '';

    /**
     * путь к файлу где лежит explain.xls
     */
    protected $explainPath;

    /**
     * @return void
     */
    public function __construct(Message $msg, array $config)
    {
        $this->msg = $msg;
        $this->config = $config;
        if ($this->explain = ($this->config['explain'] ?? APP_ENV==='debug')) {
            $this->explainPath = $this->config['explain_path'] ?? '../runtime/explain.xls';
            // удаляем файл
            if (file_exists($this->explainPath)) {
                unlink($this->explainPath);
            }
        }
    }

    /**
     * Подключаем ДБ
     * Lazy loading
     *
     * return void;
     */
    protected function connect()
    {
        if (!is_null($this->connection)) {
            return;
        }
        try {
            $this->connection = new PDO(
                $this->getDsn(),
                $this->config['user'],
                $this->config['password']
            );
        } catch (PDOException $e) {
            throw new DBALException($this->msg->i18n('Unable to connect to database.') . "\n{$e->getMessage()}");
        }
        $this->connection->exec("SET NAMES {$this->config['charset']}");
    }

    /**
     * @return PDO
     */
    abstract protected function getDsn(): string;

    abstract public function isTableExists(string $tableName): bool;

    /**
     * Извлекает поля $fields записей из таблицы $tblName по условию $where
     *
     * @param string $tblName имя таблицы
     * @param array $where условие поиска
     * @param array $fields имена полей
     * @return array
     */
    public function select(string $tblName, array $where=array(), array $fields=array()): array
    {
        $this->connect();

        $where = array_merge($where, $this->where);
        $conditions = $this->prepareConditions($where, 'where');
        $fields = array_merge($fields, $this->fields);
        $fields = $this->prepareFields($fields);

        $query = "
            SELECT $fields
            FROM $tblName
            {$this->join}
            {$conditions['clause']}
        "
        . $this->groupByString()
        . $this->orderByString()
        . $this->limit;

        // очищаем переменные
        $this
            ->clearOrderBy()
            ->clearWhere()
            ->clearFields()
            ->clearJoin()
            ->clearGroupBy()
            ->clearLimit();

        if ($this->explain) {
            $this->explain($query, $conditions);
        }
        $stmt = $this->connection->prepare($query);

        return $stmt->execute($conditions['vals']) ? $this->prepareRows($stmt->fetchAll()) : array();
    }

    /**
     * Извлекает поля $fields записи из таблицы $tblName по условию $where
     *
     * @param string $tblName имя таблицы
     * @param array $where условие поиска
     * @param array $fields имена полей
     * @return array
     */
    public function selectOne(string $tblName, array $where=array(), array $fields=array())
    {
        $rows = $this->setLimit(1)->select($tblName, $where, $fields);
        return $this->getOneRow($rows);
    }

    public function query(string $query)
    {
        $this->connect();

        $stmt = $this->connection->prepare($query);
        if (!$this->execute($stmt)) {
            return false;
        }
        return $stmt;
    }

    public function queryAll(string $query)
    {
        if ($stmt = $this->query($query)) {
            return $this->prepareRows($stmt->fetchAll());
        }
        return array();
    }

    public function queryOne(string $query)
    {
        if ($stmt = $this->query($query)) {
            return $this->prepareRows($stmt->fetch());
        }
    }

    /**
     * Вставляет записи со значениями $fieldValues
     *
     * @param string $tblName имя таблицы
     * @param array $fieldValues массив: [имена => значения] полей
     * @return mixed
     */
    public function insert(string $tblName, array $fieldValues=array())
    {
        $this->connect();

        $fieldValues = array_merge($fieldValues, $this->fields);
        $conditions = $this->prepareConditions($fieldValues, 'insert');
        $placeholder = $this->getPlaceholder($fieldValues);
        $query = "INSERT INTO `$tblName` ({$conditions['clause']}) VALUES ($placeholder)";
        $stmt = $this->connection->prepare($query);
        $this->clearFields();

        if ($this->execute($stmt, $conditions['vals'])) {
            return $this->connection->lastInsertId();
        }
        return false;
    }

    /**
     * Обновляет поля $fieldValues записей по условию $where
     *
     * @param string $tblName имя таблицы
     * @param array $fieldValues массив: [имена => значения] полей
     * @param array $where условие поиска
     * @return boolean
     */
    public function update(string $tblName, array $fieldValues=array(), array $where=array()): bool
    {
        $this->connect();

        $where = array_merge($where, $this->where);
        $fieldValues = array_merge($fieldValues, $this->fields);
        $updateConditions = $this->prepareConditions($fieldValues, 'update');
        $whereConditions = $this->prepareConditions($where, 'where');
        $query = "UPDATE $tblName {$updateConditions['clause']} {$whereConditions['clause']}";
        $stmt = $this->connection->prepare($query);
        $this->clearWhere();
        $this->clearFields();

        return $this->execute($stmt, array_merge($updateConditions['vals'], $whereConditions['vals']));
    }

    /**
     * Удаляет записи по условию $where
     *
     * @param string $tblName имя таблицы
     * @param array $where условие поиска
     * @return boolean
     */
    public function delete(string $tblName, array $where=array()): bool
    {
        $this->connect();

        $where = array_merge($where, $this->where);
        $whereConditions = $this->prepareConditions($where, 'where');
        $stmt = $this->connection->prepare("DELETE FROM `$tblName` {$whereConditions['clause']}");
        $this->clearWhere();

        return $this->execute($stmt, $whereConditions['vals']);
    }

    /**
     * Быстро очищает таблицу $tblName
     *
     * @param string $tblName имя таблицы
     * @return boolean
     */
    public function truncate(string $tblName): bool
    {
        $this->connect();
        $stmt = $this->connection->prepare("TRUNCATE `$tblName`");
        return $this->execute($stmt);
    }

    public function beginTransaction()
    {
        $this->connect();
        $this->connection->beginTransaction();
    }

    public function endTransaction()
    {
        $this->connection->commit();
    }

    # ВСПОМОГАТЕЛЬНЫЕ МЕТОДЫ

    /**
     * Добавляет условие
     */
    public function addWhere($where = null)
    {
        if (!empty($where)) {
            $this->where = array_merge($this->where, $where);
        }
    }

    /**
     * Устанавливает условие выборки
     *
     * @param array $where
     * @return void
     */
    public function setWhere($where)
    {
        $this->where = $where;
        return $this;
    }

    /**
     * возвращает условие
     */
    public function getWhere()
    {
        return $this->where;
    }

    /**
     * очищает условие
     */
    protected function clearWhere()
    {
        $this->where = array();
        return $this;
    }

    /**
     * Добавляет поля для выборки.
     */
    public function addFields($fieldNames)
    {
        $this->fields = array_merge($this->fields, $fieldNames);
    }

    /**
     * Устанавливает поля для выборки.
     */
    public function setFields($fieldNames)
    {
        $this->fields = $fieldNames;
        return $this;
    }

    /**
     * возвращает поля для выборки
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * очищает поля выборки
     */
    protected function clearFields()
    {
        $this->fields = array();
        return $this;
    }

    public function setJoin($tblName, $onCond, $joinMode='LEFT')
    {
        $this->join .= " $joinMode JOIN $tblName ON $onCond ";
        return $this;
    }

    /**
     * очищает join
     */
    protected function clearJoin()
    {
        $this->join = '';
        return $this;
    }

    /**
     * Добавляет в массив orderBy новый эт-т
     *
     * @param string $field
     * @param string $order
     * @return void
     */
    public function orderBy($fieldName, $order = 'ASC')
    {
        $this->orderBy[$fieldName] = $order;
    }

    /**
     * устанавливает orderBy
     */
    public function setOrderBy($orderBy)
    {
        $this->orderBy = $orderBy;
        return $this;
    }

    /**
     * возвращает orderBy
     */
    public function getOrderBy()
    {
        return $this->orderBy;
    }

    /**
     * очищает orderBy
     */
    protected function clearOrderBy()
    {
        $this->orderBy = array();
        return $this;
    }

    /**
     * Строка order by cast
     */
    abstract public function orderByCast(string $colName): string;

    protected function orderByString()
    {
        if (count($this->orderBy)===0) {
            return '';
        }
        $orderBy = array();
        foreach ($this->orderBy as $fieldName => $order) {
            $orderBy[] = "$fieldName $order";
        }
        return ' ORDER BY ' . implode(',', $orderBy);
    }

    public function setLimit($limit, $offset = null)
    {
        $this->limit = $limit;

        if (!is_null($offset)) {
            $this->limit = " $offset, {$this->limit}";
        }
        $this->limit = " LIMIT {$this->limit} ";
        return $this;
    }

    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * очищает limit
     */
    protected function clearLimit()
    {
        $this->limit = '';
        return $this;
    }

    public function setGroupBy($fieldName)
    {
        $this->groupBy = $fieldName;
        return $this;
    }

    public function getGroupBy()
    {
        return $this->groupBy;
    }

    /**
     * очищает GroupBy
     */
    protected function clearGroupBy()
    {
        $this->groupBy = '';
        return $this;
    }

    protected function groupByString()
    {
        if ($this->groupBy!=='')
            return " GROUP BY {$this->groupBy} ";

        return '';
    }

    protected function prepareConditions($conditions, $type, $operator='=')
    {
        switch ($type) {
            case 'where':
                return $this->createConditions($conditions, 'WHERE', "$operator ?", 'AND');
            case 'update':
                return $this->createConditions($conditions, 'SET', "$operator ?", ',');
            case 'insert':
                return $this->createConditions($conditions, '', '', ',');
            default:
                return null;
        }
    }

    protected function createConditions($conditions, $keyword, $operator, $glue)
    {
        $clause = '';
        $vals = array();
        if (count($conditions)!==0) {
            $clauseArr = array();
            foreach ($conditions as $field => $val) {
                if (preg_match('/ IN/', $field, $matches)!==0) {
                    $clauseArr[] = $this->clearifyField($field, $matches[0]) . $matches[0] . " ?";
                    $val = '(' . implode(',', $val) . ')';
                } elseif (preg_match('/ LIKE/', $field, $matches)!==0) {
                    $clauseArr[] = $this->clearifyField($field, $matches[0]) . $matches[0] . " ?";
                    $val = "%$val%";
                } elseif (preg_match('/<=|<|>=|>/', $field, $matches)!==0) {
                    $clauseArr[] = $this->clearifyField($field, $matches[0]) . $matches[0] . ' ?';
                } else {
                    $clauseArr[] = $this->prepareField($field) . $operator;
                }
                $vals[] = $val;
            }
            $clause = "$keyword " . implode(" $glue ", $clauseArr);
        }
        return compact('clause', 'vals');
    }

    protected function clearifyField($field, $text)
    {
        $field = str_replace($text, '', $field);
        $field = trim($field);
        return $this->prepareField($field);
    }

    protected function prepareFields(array $fields)
    {
        if (count($fields)==0) {
            return '*';
        }
        foreach ($fields as $key => &$field) {
            if (!is_numeric($key)) {
                $field = "$key AS $field";
            } else {
                $field = $this->prepareField($field);
            }
        }
        return implode(',', $fields);
    }

    protected function prepareField($field)
    {
        if (preg_match('/[.( ]/', $field)===0) {
            $field = "`" . trim($field) . "`";
        }
        return $field;
    }

    protected function getPlaceholder($fields)
    {
        $plholdArr = array_fill(0, count($fields), '?');

        return implode(',', $plholdArr);
    }

    protected function getOneRow($rows)
    {
        if (count($rows)>0) {
            $rows =  $this->prepareRows($rows);
            return $rows[0];
        }
    }

    protected function prepareRows($rows=array())
    {
        foreach ($rows as &$row) {
            foreach ($row as $key => $value) {
                if (is_integer($key)) {
                    unset($row[$key]);
                }
            }
        }
        return $rows;
    }

    protected function execute($stmt, $fields=null): bool
    {
        if (!$stmt->execute($fields)) {
            throw new DBALException($this->msg->i18n('Database error.'));
        }
        return true;
    }
}
