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
use tachyon\db\Query;
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
     * Соединение с БД
     */
    protected ?PDO $connection = null;
    /**
     * Параметры БД
     */
    protected array $options;
    /**
     * Выводить ли анализ запросов в файл
     */
    protected bool $explain;
    /**
     * Префикс explain
     */
    protected string $explainPrefix;
    /**
     * Путь к файлу где лежит explain.xls
     */
    protected string $explainPath;

    public function __construct(
        protected Env           $env,
        protected Message       $msg,
        protected WhereBuilder  $whereBuilder,
        protected UpdateBuilder $updateBuilder,
        protected InsertBuilder $insertBuilder,
        array $options
    ) {
        $this->options = $options;
        if ($this->explain = $this->options['explain'] ?? $this->env->isDevelop()) {
            $this->explainPath = $this->options['explain_path'] ?? APP_ROOT . '/runtime/explain.xls';
            // удаляем файл
            if (file_exists($this->explainPath)) {
                unlink($this->explainPath);
            }
        }
    }

    /**
     * Connect DB
     * Lazy loading
     *
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
     * Returns the connection string
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
     * @param Query  $query
     * @param string $tblName имя таблицы
     * @param array  $where условие поиска
     * @param array  $fields имена полей
     *
     * @throws DBALException
     */
    public function select(
        Query $query,
        string $tblName,
        array $where = [],
        array $fields = []
    ): array {
        $this->connect();
        $where = array_merge($where, $query->getWhere());
        $fields = array_merge($fields, $query->getFields());
        $expression = $this->whereBuilder->prepareExpression($where);
        $queryStr = "
            SELECT {$this->whereBuilder->prepareFields($fields)}
            FROM $tblName
            {$query->getJoin()}
            {$expression['clause']}
            {$query->groupByString()}
            {$query->orderByString()}
            {$query->getLimit()}
        ";

        if ($this->explain) {
            $this->explain($queryStr, $expression);
        }
	    if (!$stmt = $this->connection->prepare($queryStr)) {
		    throw new DBALException(t('Error during prepare query.'));
	    }
        //  clean variables
        $query
            ->clearWhere()
            ->clearOrderBy()
            ->clearFields()
            ->clearJoin()
            ->clearGroupBy()
            ->clearLimit();

        return $stmt->execute($expression['vals']) ? $this->prepareRows($stmt->fetchAll()) : [];
    }

    /**
     * Извлекает поля $fields записи из таблицы $tblName по условию $where
     *
     * @param Query  $query
     * @param string $tblName имя таблицы
     * @param array  $where условие поиска
     * @param array  $fields имена полей
     *
     * @throws DBALException
     */
    public function selectOne(
        Query $query,
        string $tblName,
        array $where = [],
        array $fields = [],
    ): mixed {
        $query->setLimit(1);
        $rows = $this->select($query, $tblName, $where, $fields);

        return $this->getOneRow($rows);
    }

    /**
     * Вставляет записи со значениями $fields в таблицу $tblName
     *
     * @param Query  $query
     * @param string $tblName имя таблицы
     * @param array  $fields массив: [имена => значения] полей
     *
     * @throws DBALException
     */
    public function insert(
        Query $query,
        string $tblName,
        array $fields = []
    ): ?string {
        $this->connect();
        $fields = array_merge($fields, $query->getFields());
        $expression = $this->insertBuilder->prepareExpression($fields);
        if (!$stmt = $this->connection->prepare("
            INSERT INTO `$tblName`
            ({$expression['clause']}) 
            VALUES ({$this->getPlaceholder($expression['vals'])})
        ")) {
            throw new DBALException('Error during prepare insert statement.');
        }
        $query->clearFields();
        if ($this->execute($stmt, $expression['vals'])) {
            return $this->connection->lastInsertId();
        }
        return null;
    }

    /**
     * Обновляет поля таблицы $tblName $fields записей по условию $where
     *
     * @param Query  $query
     * @param string $tblName имя таблицы
     * @param array  $fields  массив: [имена => значения] полей
     * @param array  $where   условие поиска
     *
     * @throws DBALException
     */
    public function update(
        Query  $query,
        string $tblName,
        array  $fields = [],
        array  $where = []
    ): bool {
        $this->connect();
        $where = array_merge($where, $query->getWhere());
        $fields = array_merge($fields, $query->getFields());
        $updateExpression = $this->updateBuilder->prepareExpression($fields);
        $whereExpression = $this->whereBuilder->prepareExpression($where);
        if (!$stmt = $this->connection->prepare("
            UPDATE $tblName 
            {$updateExpression['clause']} 
            {$whereExpression['clause']}
        ")) {
            throw new DBALException('Error during prepare update statement.');
        }
        $query->clearWhere();
        $query->clearFields();
        return $this->execute($stmt, array_merge($updateExpression['vals'], $whereExpression['vals']));
    }

    /**
     * Удаляет записи из таблицы $tblName по условию $where
     *
     * @param Query $query
     * @param string $tblName имя таблицы
     * @param array  $where условие поиска
     *
     * @throws DBALException
     */
    public function delete(
        Query $query,
        string $tblName,
        array $where = []
    ): bool {
        $this->connect();
        $where = array_merge($where, $query->getWhere());
        $expression = $this->whereBuilder->prepareExpression($where);
        if (!$stmt = $this->connection->prepare("
            DELETE FROM `$tblName`
            {$expression['clause']}
        ")) {
            throw new DBALException('Error during prepare delete statement.');
        }
        $query->clearWhere();

        return $this->execute($stmt, $expression['vals']);
    }

    /**
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
    public function queryOne(string $query): ?array
    {
        if ($stmt = $this->query($query)) {
            return $this->prepareRows($stmt->fetch());
        }
        return null;
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
     * Строка order by cast
     */
    abstract public function orderByCast(string $colName): string;

    protected function getPlaceholder(array $fields): string
    {
        $placeholderArr = array_fill(0, count($fields), '?');
        return implode(',', $placeholderArr);
    }

    /**
     * Возвращение одной строки из извлеченного массива строк
     */
    protected function getOneRow(array $rows = []): ?array
    {
        if (count($rows) > 0) {
            $rows = $this->prepareRows($rows);
            return $rows[0];
        }
        return null;
    }

	/**
	 * Подготовка извлеченного массива строк, удаление лишнего
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
     * @throws DBALException
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
