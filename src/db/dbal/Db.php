<?php

namespace tachyon\db\dbal;

use PDO,
    PDOException,
    tachyon\exceptions\DBALException,
    tachyon\components\Message;

/**
 * DBAL
 *
 * @author Андрей Сердюк
 * @copyright (c) 2019 IMND
 */
abstract class Db
{
    /**
     * параметры БД
     *
     * @var array
     */
    protected $config;
    /**
     * соединение с БД
     *
     * @var PDO
     */
    protected $connection;
    /**
     * Компонент msg
     *
     * @var Message
     */
    protected $msg;
    /**
     * Выводить ли анализ запросов в файл
     *
     * @var boolean
     */
    protected $explain;
    /**
     * поля для выборки/вставки/обновления
     *
     * @var array
     */
    protected $fields = [];
    /**
     * условия для выборки
     *
     * @var array
     */
    protected $where = [];
    /**
     * @var string
     */
    protected $join = '';
    /**
     * Поле группировки
     *
     * @var string
     */
    protected $groupBy = '';
    /**
     * Поля сортировки
     *
     * @var array
     */
    protected $orderBy = [];
    /**
     * LIMIT
     *
     * @var string
     */
    protected $limit = '';

    /**
     * Путь к файлу где лежит explain.xls
     *
     * @var string
     */
    protected $explainPath;

    /**
     * @param Message $msg
     * @param array   $config настройки
     *
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
     * @return void
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
     * Возвращаетстроку соединения
     *
     * @return string
     */
    abstract protected function getDsn(): string;

    /**
     * Проверка существования таблицы $tableName
     *
     * @param string $tableName
     *
     * @return boolean
     */
    abstract public function isTableExists(string $tableName): bool;

    /**
     * Извлекает поля $fields записей из таблицы $tblName по условию $where
     *
     * @param string $tblName имя таблицы
     * @param array  $where условие поиска
     * @param array  $fields имена полей
     *
     * @return array
     */
    public function select(
        string $tblName,
        array $where = [],
        array $fields = []
    ): array {
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
        return $stmt->execute($conditions['vals']) ? $this->prepareRows($stmt->fetchAll()) : [];
    }

    /**
     * Извлекает поля $fields записи из таблицы $tblName по условию $where
     *
     * @param string $tblName имя таблицы
     * @param array  $where условие поиска
     * @param array  $fields имена полей
     *
     * @return array
     */
    public function selectOne(string $tblName, array $where = [], array $fields = []): array
    {
        $rows = $this->setLimit(1)->select($tblName, $where, $fields);
        return $this->getOneRow($rows);
    }

    /**
     * Выполняет запрос $query
     *
     * @param string $query
     *
     * @return mixed
     */
    public function query(string $query)
    {
        $this->connect();
        $stmt = $this->connection->prepare($query);
        if (!$this->execute($stmt)) {
            return false;
        }
        return $stmt;
    }

    /**
     * Выполняет запрос $query и возвращает результат в виде массива записей
     * 
     * @param string $query
     * @return array
     */
    public function queryAll(string $query): array
    {
        if ($stmt = $this->query($query)) {
            return $this->prepareRows($stmt->fetchAll());
        }
        return [];
    }

    /**
     * Выполняет запрос $query и возвращает одну запись в виде массива
     * 
     * @param string $query
     * @return array
     */
    public function queryOne(string $query): array
    {
        if ($stmt = $this->query($query)) {
            return $this->prepareRows($stmt->fetch());
        }
    }

    /**
     * Вставляет записи со значениями $fieldValues в таблицу $tblName
     *
     * @param string $tblName имя таблицы
     * @param array  $fieldValues массив: [имена => значения] полей
     *
     * @return mixed
     */
    public function insert(string $tblName, array $fieldValues = [])
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
     * Обновляет поля таблицы $tblName $fieldValues записей по условию $where
     *
     * @param string $tblName имя таблицы
     * @param array  $fieldValues массив: [имена => значения] полей
     * @param array  $where условие поиска
     *
     * @return boolean
     */
    public function update(
        string $tblName,
        array $fieldValues = [],
        array $where = []
    ): bool {
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
     * Удаляет записи из таблицы $tblName по условию $where
     *
     * @param string $tblName имя таблицы
     * @param array  $where условие поиска
     *
     * @return boolean
     */
    public function delete(string $tblName, array $where = []): bool
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
     *
     * @return boolean
     */
    public function truncate(string $tblName): bool
    {
        $this->connect();
        $stmt = $this->connection->prepare("TRUNCATE `$tblName`");
        return $this->execute($stmt);
    }

    /**
     * Инициирует транзакцию
     * 
     * @return void
     */
    public function beginTransaction(): void
    {
        $this->connect();
        $this->connection->beginTransaction();
    }

    /**
     * Оканчивает транзакцию
     * 
     * @return void
     */
    public function endTransaction()
    {
        $this->connection->commit();
    }

    # ВСПОМОГАТЕЛЬНЫЕ МЕТОДЫ

    /**
     * Добавляет условие
     * 
     * @param array $where условия
     * 
     * @return Db
     */
    public function addWhere(array $where = null): Db
    {
        if (!empty($where)) {
            $this->where = array_merge($this->where, $where);
        }
        return $this;
    }

    /**
     * Устанавливает условие выборки
     *
     * @param array $where условия
     *
     * @return Db
     */
    public function setWhere(array $where): Db
    {
        $this->where = $where;
        return $this;
    }

    /**
     * Возвращает условие
     * 
     * @return string
     */
    public function getWhere(): string
    {
        return $this->where;
    }

    /**
     * Очищает условие
     * 
     * @return Db
     */
    protected function clearWhere(): Db
    {
        $this->where = [];
        return $this;
    }

    /**
     * Добавляет поля для выборки
     * 
     * @return Db
     */
    public function addFields(array $fieldNames): Db
    {
        $this->fields = array_merge($this->fields, $fieldNames);
        return $this;
    }

    /**
     * Устанавливает поля для выборки
     * 
     * @return Db
     */
    public function setFields(array $fieldNames): Db
    {
        $this->fields = $fieldNames;
        return $this;
    }

    /**
     * Возвращает поля для выборки
     * 
     * @return array
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * Очищает поля выборки
     * 
     * @return Db
     */
    protected function clearFields(): Db
    {
        $this->fields = [];
        return $this;
    }

    /**
     * Устанавливает строку для JOIN
     * 
     * @param string $tblName
     * @param string $onCond
     * @param string $joinMode
     * @return Db
     */
    public function setJoin(string $tblName, string $onCond, string $joinMode = 'LEFT'): Db
    {
        $this->join = " $joinMode JOIN $tblName ON $onCond ";
        return $this;
    }

    /**
     * Добавляет строку для JOIN
     * 
     * @param string $tblName
     * @param string $onCond
     * @param string $joinMode
     * @return Db
     */
    public function addJoin(string $tblName, string $onCond, string $joinMode = 'LEFT'): Db
    {
        $this->join .= " $joinMode JOIN $tblName ON $onCond ";
        return $this;
    }

    /**
     * Очищает join
     * @return Db
     */
    protected function clearJoin(): Db
    {
        $this->join = '';
        return $this;
    }

    /**
     * Добавляет в массив orderBy новый элемент
     *
     * @param string $field
     * @param string $order
     *
     * @return Db
     */
    public function orderBy(string $fieldName, string $order = 'ASC'): Db
    {
        $this->orderBy[$fieldName] = $order;
        return $this;
    }

    /**
     * Устанавливает orderBy
     * @return Db
     */
    public function setOrderBy($orderBy): Db
    {
        $this->orderBy = $orderBy;
        return $this;
    }

    /**
     * Возвращает orderBy
     * @return string
     */
    public function getOrderBy(): string
    {
        return $this->orderBy;
    }

    /**
     * Очищает orderBy
     * @return Db
     */
    protected function clearOrderBy(): Db
    {
        $this->orderBy = [];
        return $this;
    }

    /**
     * Строка order by cast
     * 
     * @param string $colName
     * 
     * @return string
     */
    abstract public function orderByCast(string $colName): string;

    /**
     * Возвращает форматированную строку ORDER BY
     * 
     * @return string
     */
    private function orderByString(): string
    {
        if (count($this->orderBy) === 0) {
            return '';
        }
        $orderBy = [];
        foreach ($this->orderBy as $fieldName => $order) {
            $orderBy[] = "$fieldName $order";
        }

        return ' ORDER BY ' . implode(',', $orderBy);
    }

    /**
     * Устанавливает форматированную строку LIMIT
     * 
     * @param numeric $limit
     * @param numeric $offset
     * 
     * @return Db
     */
    public function setLimit($limit, $offset = null): Db
    {
        $this->limit = $limit;

        if (!is_null($offset)) {
            $this->limit = " $offset, {$this->limit}";
        }
        $this->limit = " LIMIT {$this->limit} ";

        return $this;
    }

    /**
     * Возвращает limit
     * 
     * @return string
     */
    public function getLimit(): string
    {
        return $this->limit;
    }

    /**
     * Очищает limit
     * 
     * @return Db
     */
    protected function clearLimit(): Db
    {
        $this->limit = '';
        return $this;
    }

    /**
     * Устанавливает groupBy
     * 
     * @return Db
     */
    public function setGroupBy(string $fieldName): Db
    {
        $this->groupBy = $fieldName;
        return $this;
    }

    /**
     * Возвращает groupBy
     * 
     * @return string
     */
    public function getGroupBy(): string
    {
        return $this->groupBy;
    }

    /**
     * Очищает groupBy
     * 
     * @return Db
     */
    protected function clearGroupBy(): Db
    {
        $this->groupBy = '';
        return $this;
    }

    /**
     * Возвращает форматированную строку GROUP BY
     * 
     * @return string
     */
    private function groupByString(): string
    {
        if ($this->groupBy !== '') {
            return " GROUP BY {$this->groupBy} ";

        return '';
    }

    /**
     * Форматирует условия для выборки, вставки или удаления
     * 
     * @param array $conditions
     * @param string $type
     * @param string $operator
     * 
     * @return string
     */
    protected function prepareConditions(array $conditions, string $type, string $operator = '='): string
    {
        switch ($type) {
            case 'where':
                return $this->createConditions($conditions, 'WHERE', "$operator ?", 'AND');
            case 'update':
                return $this->createConditions($conditions, 'SET', "$operator ?", ',');
            case 'insert':
                return $this->createConditions($conditions, '', '', ',');
            default:
                return '';
        }
    }

    /**
     * Форматирует условия для выборки, вставки или удаления
     * 
     * @param array  $conditions
     * @param string $keyword
     * @param string $operator
     * @param string $glue
     * 
     * @return string
     */
    protected function createConditions(array $conditions, string $keyword, string $operator, string $glue): string
    {
        $clause = '';
        $vals = [];
        if (count($conditions) !== 0) {
            $clauseArr = [];
            foreach ($conditions as $field => $val) {
                if (preg_match('/ IN/', $field, $matches) !== 0) {
                    $clauseArr[] = $this->clearifyField($field, $matches[0]) . $matches[0] . " ?";
                    $val = '(' . implode(',', $val) . ')';
                } elseif (preg_match('/ LIKE/', $field, $matches) !== 0) {
                    $clauseArr[] = $this->clearifyField($field, $matches[0]) . $matches[0] . " ?";
                    $val = "%$val%";
                } elseif (preg_match('/<=|<|>=|>/', $field, $matches) !== 0) {
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

    /**
     * Очистка поля
     * 
     * @param string $field
     * @param string $text
     * @return string
     */
    protected function clearifyField(string $field, string $text): string
    {
        $field = str_replace($text, '', $field);
        $field = trim($field);
        return $this->prepareField($field);
    }

    /**
     * Подготовка поля
     * 
     * @param array $fields
     * @return string
     */
    protected function prepareFields(array $fields): string
    {
        if (count($fields) == 0) {
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

    /**
     * Снабжение поля кавычками
     * 
     * @param string $field
     * @return string
     */
    protected function quoteField(string $field): string
    {
        if (preg_match('/[.( ]/', $field) === 0) {
            $field = "`" . trim($field) . "`";
        }
        return $field;
    }

    /**
     * @param array $fields
     * @return string
     */
    protected function getPlaceholder(array $fields): string
    {
        $placeholderArr = array_fill(0, count($fields), '?');
        return implode(',', $placeholderArr);
    }

    /**
     * Возвращение одного поля из извлеченного массива строк
     * 
     * @param array $rows
     * @return 
     */
    protected function getOneRow(array $rows = []): array
    {
        if (count($rows) > 0) {
            $rows = $this->prepareRows($rows);
            return $rows[0];
        }
    }

    /**
     * Подготовка извлеченного массива строк, удаление лишнего
     * 
     * @param array $rows
     * @return array
     */
    protected function prepareRows(array $rows = []): array
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

    /**
     * Выполнение запроса
     * 
     * @param PDOStatemnt $stmt
     * @param array $fields
     * 
     * @return boolean
     * 
     * @throws DBALException
     */
    protected function execute($stmt, $fields = null): bool
    {
        if (!$stmt->execute($fields)) {
            throw new DBALException($this->msg->i18n('Database error.'));
        }
        return true;
    }
}
