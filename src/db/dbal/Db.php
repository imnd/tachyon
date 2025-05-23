<?php

namespace tachyon\db\dbal;

use Exception;
use PDO,
    PDOException,
    PDOStatement,
    tachyon\exceptions\DBALException,
    tachyon\components\Message,
    tachyon\Env
;
use tachyon\db\dbal\conditions\{
    WhereBuilder, UpdateBuilder, InsertBuilder
};

/**
 * DBAL
 *
 * @author imndsu@gmail.com
 */
abstract class Db
{
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
     * Компонент msg
     *
     * @var Message
     */
    protected Message $msg;
    /**
     * @var Env $env
     */
    protected $env;

    /**
     * соединение с БД
     *
     * @var PDO
     */
    protected ?PDO $connection = null;

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
     * @var string
     */
    protected string $explainPrefix;

    /**
     * Путь к файлу где лежит explain.xls
     *
     * @var string
     */
    protected string $explainPath;

    /**
     * @param Env           $env
     * @param Message       $msg
     * @param WhereBuilder  $whereBuilder
     * @param UpdateBuilder $updateBuilder
     * @param InsertBuilder $insertBuilder
     * @param array         $options
     */
    public function __construct(
        Env           $env,
        Message       $msg,
        WhereBuilder  $whereBuilder,
        UpdateBuilder $updateBuilder,
        InsertBuilder $insertBuilder,
        array $options
    ) {
        $this->env           = $env;
        $this->msg           = $msg;
        $this->whereBuilder  = $whereBuilder;
        $this->updateBuilder = $updateBuilder;
        $this->insertBuilder = $insertBuilder;
        $this->options       = $options;
        if ($this->explain = $this->options['explain'] ?? $this->env->isDevelop()) {
            $this->explainPath = $this->options['explain_path'] ?? __DIR__ . '/../../../../../../runtime/explain.xls';
            // удаляем файл
            if (file_exists($this->explainPath)) {
                unlink($this->explainPath);
            }
        }
    }

    /**
     * connect db
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
                $this->connection->exec("SET NAMES '{$this->options['charset']}'");
                if ($this->env->isDevelop()) {
                    $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                }
            }
        } catch (PDOException $e) {
            throw new DBALException(t('Unable to connect to database.') . "\n{$e->getMessage()}");
        }
    }

    /**
     * returns the connection string
     */
    abstract protected function getDsn(): string;

    /**
     * checks if the table $tableName exists
     *
     * @throws DBALException
     */
    abstract public function isTableExists(string $tableName): bool;

    /**
     * extracts fields $fields of the records from the table $tblName by condition $where
     *
     * @param string $tblName имя таблицы
     * @param array  $where условие поиска
     * @param array  $fields имена полей
     *
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
        $expression = $this->whereBuilder->prepareExpression($where);
        $query = "
            SELECT {$this->whereBuilder->prepareFields($fields)}
            FROM $tblName
            {$this->join}
            {$expression['clause']}
            {$this->groupByString()}
            {$this->orderByString()}
            {$this->limit}
        ";

        if ($this->explain) {
            $this->explain($query, $expression);
        }
	    if (!$stmt = $this->connection->prepare($query)) {
		    throw new DBALException(t('Error during prepare query.'));
	    }
        //  clean variables
        $this
            ->clearOrderBy()
            ->clearWhere()
            ->clearFields()
            ->clearJoin()
            ->clearGroupBy()
            ->clearLimit();

        return $stmt->execute($expression['vals']) ? $this->prepareRows($stmt->fetchAll()) : [];
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
     * @return string | null
     * @throws DBALException
     */
    public function insert(string $tblName, array $fields = []): ?string
    {
        $this->connect();
        $fields = array_merge($fields, $this->fields);
        $expression = $this->insertBuilder->prepareExpression($fields);
        if (!$stmt = $this->connection->prepare("
            INSERT INTO `$tblName`
            ({$expression['clause']}) 
            VALUES ({$this->getPlaceholder($expression['vals'])})
        ")) {
            throw new DBALException('Error during prepare insert statement.');
        }
        $this->clearFields();
        if ($this->execute($stmt, $expression['vals'])) {
            return $this->connection->lastInsertId();
        }
        return null;
    }

    /**
     * Обновляет поля таблицы $tblName $fields записей по условию $where
     *
     * @param string $tblName имя таблицы
     * @param array  $fields  массив: [имена => значения] полей
     * @param array  $where   условие поиска
     *
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
        $updateExpression = $this->updateBuilder->prepareExpression($fields);
        $whereExpression = $this->whereBuilder->prepareExpression($where);
        if (!$stmt = $this->connection->prepare("
            UPDATE $tblName 
            {$updateExpression['clause']} 
            {$whereExpression['clause']}
        ")) {
            throw new DBALException('Error during prepare update statement.');
        }
        $this->clearWhere();
        $this->clearFields();
        return $this->execute($stmt, array_merge($updateExpression['vals'], $whereExpression['vals']));
    }

    /**
     * Удаляет записи из таблицы $tblName по условию $where
     *
     * @param string $tblName имя таблицы
     * @param array  $where условие поиска
     *
     * @throws DBALException
     */
    public function delete(string $tblName, array $where = []): bool
    {
        $this->connect();
        $where = array_merge($where, $this->where);
        $expression = $this->whereBuilder->prepareExpression($where);
        if (!$stmt = $this->connection->prepare("
            DELETE FROM `$tblName`
            {$expression['clause']}
        ")) {
            throw new DBALException('Error during prepare delete statement.');
        }
        $this->clearWhere();

        return $this->execute($stmt, $expression['vals']);
    }

    /**
     * Быстро очищает таблицу $tblName
     *
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
     * @throws DBALException
     */
    public function query(string $query): PDOStatement | false
    {
        $this->connect();
        if (!$stmt = $this->connection->prepare($query)) {
            throw new DBALException(t('Error during prepare query.'));
        }
        if (!$this->execute($stmt)) {
            return false;
        }
        return $stmt;
    }

    /**
     * Выполняет запрос $query и возвращает результат в виде массива записей
     *
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
     * @throws DBALException
     */
    public function beginTransaction(): void
    {
        $this->connect();
        $this->connection->beginTransaction();
    }

    /**
     * Оканчивает транзакцию
     */
    public function endTransaction(): void
    {
        $this->connection->commit();
    }

    # ВСПОМОГАТЕЛЬНЫЕ МЕТОДЫ

    /**
     * Добавляет условие
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
     */
    public function setWhere(array $where): Db
    {
        $this->where = $where;
        return $this;
    }

    /**
     * Возвращает условие
     */
    public function getWhere(): array
    {
        return $this->where;
    }

    /**
     * Очищает условие
     */
    protected function clearWhere(): Db
    {
        $this->where = [];
        return $this;
    }

    /**
     * Добавляет поля для выборки
     */
    public function addFields(array $fieldNames): Db
    {
        $this->fields = array_merge($this->fields, $fieldNames);
        return $this;
    }

    /**
     * Устанавливает поля для выборки
     */
    public function setFields(array $fieldNames): Db
    {
        $this->fields = $fieldNames;
        return $this;
    }

    /**
     * Возвращает поля для выборки
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * Очищает поля выборки
     */
    protected function clearFields(): Db
    {
        $this->fields = [];
        return $this;
    }

    /**
     * Устанавливает строку для JOIN
     */
    public function setJoin(string $tblName, string $onCond, string $joinMode = 'LEFT'): Db
    {
        $this->join = " $joinMode JOIN $tblName ON $onCond ";
        return $this;
    }

    /**
     * Добавляет строку для JOIN
     */
    public function addJoin(string $tblName, string $onCond, string $joinMode = 'LEFT'): Db
    {
        $this->join .= " $joinMode JOIN $tblName ON $onCond ";
        return $this;
    }

    /**
     * Очищает join
     */
    protected function clearJoin(): Db
    {
        $this->join = '';
        return $this;
    }

    /**
     * Добавляет в массив orderBy новый элемент
     */
    public function orderBy(string $fieldName, string $order = 'ASC'): Db
    {
        $this->orderBy[$fieldName] = $order;
        return $this;
    }

    /**
     * Устанавливает orderBy
     */
    public function setOrderBy($orderBy): Db
    {
        $this->orderBy = $orderBy;
        return $this;
    }

    /**
     * Возвращает orderBy
     */
    public function getOrderBy(): array
    {
        return $this->orderBy;
    }

    /**
     * Очищает orderBy
     */
    protected function clearOrderBy(): Db
    {
        $this->orderBy = [];
        return $this;
    }

    /**
     * Строка order by cast
     */
    abstract public function orderByCast(string $colName): string;

    /**
     * Возвращает форматированную строку ORDER BY
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
     */
    public function getLimit(): string
    {
        return $this->limit;
    }

    /**
     * Очищает limit
     */
    protected function clearLimit(): Db
    {
        $this->limit = '';
        return $this;
    }

    /**
     * Устанавливает groupBy
     */
    public function setGroupBy(string $fieldName): Db
    {
        $this->groupBy = $fieldName;
        return $this;
    }

    /**
     * Возвращает groupBy
     */
    public function getGroupBy(): string
    {
        return $this->groupBy;
    }

    /**
     * Очищает groupBy
     */
    protected function clearGroupBy(): Db
    {
        $this->groupBy = '';
        return $this;
    }

    /**
     * Возвращает форматированную строку GROUP BY
     */
    private function groupByString(): string
    {
        if ($this->groupBy !== '') {
            return " GROUP BY {$this->groupBy} ";
        }
        return '';
    }

    protected function getPlaceholder(array $fields): string
    {
        $placeholderArr = array_fill(0, count($fields), '?');
        return implode(',', $placeholderArr);
    }

    /**
     * Возвращение одного поля из извлеченного массива строк
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
     * @throws DBALException
     */
    protected function execute(PDOStatement $stmt, array $fields = null): bool
    {
        if (!$stmt->execute($fields)) {
            throw new DBALException(t('Database error.'));
        }
        return true;
    }

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
    protected function explain(
        string $query,
        array  $conditions1,
        array  $conditions2 = null
    ): void
    {
        $query = "{$this->explainPrefix} " . trim(preg_replace('!\s+!', ' ', str_replace(["\r", "\n"], ' ', $query)));
        $output = "query: $query\r\nid\tselect_type\ttable\ttype\tpossible_keys\tkey\tkey_len\tref\trows\tExtra\r\n";

        $fields = $conditions1['vals'];
        if (!is_null($conditions2)) {
            $fields = array_merge($fields, $conditions2['vals']);
        }

        $stmt = $this->connection->prepare($query);
        try {
            $this->execute($stmt, $fields);
            $rows = $stmt->fetchAll();
            foreach ($rows as $row) {
                foreach ($row as $key => $value) {
                    if (is_numeric($key)) {
                        $output .= "$value\t";
                    }
                }
                $output .= "\r\n";
            }
            // выводим в файл
            $file = fopen($this->explainPath, 'w');
            fwrite($file, $output);
            fclose($file);
        } catch (Exception $e) {
            throw new DBALException($e->getMessage());
        }
    }
}
