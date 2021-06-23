<?php

namespace tachyon\db\dbal;

use PDO,
    PDOException,
    PDOStatement,
    tachyon\exceptions\DBALException,
    tachyon\components\Message,
    tachyon\Config
;
use tachyon\db\dbal\conditions\{
    WhereBuilder, UpdateBuilder, InsertBuilder
};

/**
 * DBAL
 *
 * @author Андрей Сердюк
 * @copyright (c) 2020 IMND
 */
abstract class Db
{
    /**
     * @var Config $config
     */
    protected $config;
    /**
     * @var WhereBuilder $whereBuilder
     */
    protected $whereBuilder;
    /**
     * @var UpdateBuilder $updateBuilder
     */
    protected $updateBuilder;
    /**
     * @var InsertBuilder $insertBuilder
     */
    protected $insertBuilder;
    /**
     * соединение с БД
     *
     * @var PDO
     */
    protected ?PDO $connection = null;
    /**
     * Компонент msg
     *
     * @var Message
     */
    protected Message $msg;
    /**
     * параметры БД
     *
     * @var array
     */
    protected array $options;
    /**
     * Выводить ли анализ запросов в файл
     *
     * @var boolean
     */
    protected bool $explain;
    /**
     * поля для выборки/вставки/обновления
     *
     * @var array
     */
    protected array $fields = [];
    /**
     * условия для выборки
     *
     * @var array
     */
    protected array $where = [];
    /**
     * @var string
     */
    protected string $join = '';
    /**
     * Поле группировки
     *
     * @var string
     */
    protected string $groupBy = '';
    /**
     * Поля сортировки
     *
     * @var array
     */
    protected array $orderBy = [];
    /**
     * LIMIT
     *
     * @var string
     */
    protected string $limit = '';

    /**
     * Путь к файлу где лежит explain.xls
     *
     * @var string
     */
    protected $explainPath;

    /**
     * @param Message $msg
     * @param Config  $config настройки
     * @param array   $options
     */
    public function __construct(
        Message $msg,
        Config $config,
        WhereBuilder $whereBuilder,
        UpdateBuilder $updateBuilder,
        InsertBuilder $insertBuilder,
        array $options
    ) {
        $this->msg          = $msg;
        $this->config       = $config;
        $this->whereBuilder = $whereBuilder;
        $this->updateBuilder = $updateBuilder;
        $this->insertBuilder = $insertBuilder;
        $this->options      = $options;
        if ($this->explain  = $this->options['explain'] ?? $this->config->get('env')==='debug') {
            $this->explainPath = $this->options['explain_path'] ?? '../runtime/explain.xls';
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
     * @throws DBALException
     */
    protected function connect(): void
    {
        try {
            if (is_null($this->connection)) {
                $this->connection = new PDO(
                    $this->getDsn(),
                    $this->options['user'],
                    $this->options['password']
                );
                $this->connection->exec("SET NAMES {$this->options['charset']}");
            }
        } catch (PDOException $e) {
            throw new DBALException($this->msg->i18n('Unable to connect to database.') . "\n{$e->getMessage()}");
        }
    }

    /**
     * Возвращаетстроку соединения
     *
     * @return string
     */
    abstract protected function getDsn(): string;

    /**
     * Выдает отчет EXPLAIN
     *
     * @param string     $query
     * @param array      $conditions1
     * @param array|null $conditions2
     *
     * @throws DBALException
     * @return void
     */
    abstract protected function explain(
        string $query,
        array  $conditions1,
        array  $conditions2 = null
    ): void;

    /**
     * Проверка существования таблицы $tableName
     *
     * @param string $tableName
     *
     * @return boolean
     * @throws DBALException
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
     * @throws DBALException
     */
    public function select(
        string $tblName,
        array $where = [],
        array $fields = []
    ): array {
        $this->connect();
        $where = array_merge($where, $this->where);
        $fields = array_merge($fields, $this->fields);
        $conditions = $this->whereBuilder->prepareConditions($where);
        $query = "
            SELECT {$this->whereBuilder->prepareFields($fields)}
            FROM $tblName
            {$this->join}
            {$conditions['clause']}
            {$this->groupByString()}
            {$this->orderByString()}
            {$this->limit}
        ";

        if ($this->explain) {
            $this->explain($query, $conditions);
        }
	    if (!$stmt = $this->connection->prepare($query)) {
		    throw new DBALException($this->msg->i18n('Error during prepare query.'));
	    }
        // очищаем переменные
        $this
            ->clearOrderBy()
            ->clearWhere()
            ->clearFields()
            ->clearJoin()
            ->clearGroupBy()
            ->clearLimit();

        return $stmt->execute($conditions['vals']) ? $this->prepareRows($stmt->fetchAll()) : [];
    }

    /**
     * Извлекает поля $fields записи из таблицы $tblName по условию $where
     *
     * @param string $tblName имя таблицы
     * @param array  $where условие поиска
     * @param array  $fields имена полей
     *
     * @return mixed
     * @throws DBALException
     */
    public function selectOne(string $tblName, array $where = [], array $fields = [])
    {
        $rows = $this->setLimit(1)->select($tblName, $where, $fields);
        return $this->getOneRow($rows);
    }

    /**
     * Вставляет записи со значениями $fields в таблицу $tblName
     *
     * @param string $tblName имя таблицы
     * @param array  $fields массив: [имена => значения] полей
     *
     * @return mixed
     * @throws DBALException
     */
    public function insert(string $tblName, array $fields = [])
    {
        $this->connect();
        $fields = array_merge($fields, $this->fields);
        $conditions = $this->insertBuilder->prepareConditions($fields);
        if (!$stmt = $this->connection->prepare("
            INSERT INTO `$tblName`
            ({$conditions['clause']}) 
            VALUES ({$this->getPlaceholder($fields)})
        ")) {
            throw new DBALException('Error during prepare insert statement.');
        }
        $this->clearFields();
        if ($this->execute($stmt, $conditions['vals'])) {
            return $this->connection->lastInsertId();
        }
        return false;
    }

    /**
     * Обновляет поля таблицы $tblName $fields записей по условию $where
     *
     * @param string $tblName имя таблицы
     * @param array  $fields массив: [имена => значения] полей
     * @param array  $where условие поиска
     *
     * @return boolean
     * @throws DBALException
     */
    public function update(
        string $tblName,
        array $fields = [],
        array $where = []
    ): bool {
        $this->connect();
        $where = array_merge($where, $this->where);
        $fields = array_merge($fields, $this->fields);
        $updateConditions = $this->updateBuilder->prepareConditions($fields);
        $whereConditions = $this->whereBuilder->prepareConditions($fields);
        if (!$stmt = $this->connection->prepare("
            UPDATE $tblName 
            {$updateConditions['clause']} 
            {$whereConditions['clause']}
        ")) {
            throw new DBALException('Error during prepare update statement.');
        }
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
     * @throws DBALException
     */
    public function delete(string $tblName, array $where = []): bool
    {
        $this->connect();
        $where = array_merge($where, $this->where);
        $whereConditions = $this->whereBuilder->prepareConditions($where);
        if (!$stmt = $this->connection->prepare("
            DELETE FROM `$tblName`
            {$whereConditions['clause']}
        ")) {
            throw new DBALException('Error during prepare delete statement.');
        }
        $this->clearWhere();

        return $this->execute($stmt, $whereConditions['vals']);
    }

    /**
     * Быстро очищает таблицу $tblName
     *
     * @param string $tblName имя таблицы
     *
     * @return boolean
     * @throws DBALException
     */
    public function truncate(string $tblName): bool
    {
        $this->connect();
        $stmt = $this->connection->prepare("TRUNCATE `$tblName`");
        return $this->execute($stmt);
    }

    /**
     * Выполняет запрос $query
     *
     * @param string $query
     *
     * @return mixed
     * @throws DBALException
     */
    public function query(string $query)
    {
        $this->connect();
        if (!$stmt = $this->connection->prepare($query)) {
            throw new DBALException($this->msg->i18n('Error during prepare query.'));
        }
        if (!$this->execute($stmt)) {
            return false;
        }
        return $stmt;
    }

    /**
     * Выполняет запрос $query и возвращает результат в виде массива записей
     *
     * @param string $query
     *
     * @return array
     * @throws DBALException
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
     *
     * @return array
     * @throws DBALException
     */
    public function queryOne(string $query): array
    {
        if ($stmt = $this->query($query)) {
            return $this->prepareRows($stmt->fetch());
        }
    }

    /**
     * Инициирует транзакцию
     *
     * @return void
     * @throws DBALException
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
    public function endTransaction(): void
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
     * @return array
     */
    public function getWhere(): array
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
     * @param array $fieldNames
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
     * @param array $fieldNames
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
     * @param string $fieldName
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
     *
     * @param $orderBy
     *
     * @return Db
     */
    public function setOrderBy($orderBy): Db
    {
        $this->orderBy = $orderBy;
        return $this;
    }

    /**
     * Возвращает orderBy
     * @return array
     */
    public function getOrderBy(): array
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
     * @param int $limit
     * @param int $offset
     *
     * @return Db
     */
    public function setLimit(int $limit, int $offset = null): Db
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
     * @param string $fieldName
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
        }
        return '';
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
     * @return mixed
     */
    protected function getOneRow(array $rows = [])
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
			$row = $this->prepareRow($row);
		}
		return $rows;
	}

	/**
	 * Подготовка извлеченной строки, удаление лишнего
	 *
	 * @param array $row
	 * @return array
	 */
	protected function prepareRow(array $row = []): array
	{
		foreach ($row as $key => $value) {
			if (is_int($key)) {
				unset($row[$key]);
			}
		}
		return $row;
	}

    /**
     * Выполнение запроса
     *
     * @param PDOStatement $stmt
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
