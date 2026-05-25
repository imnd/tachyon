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
     * DB connection
     */
    protected ?PDO $connection = null;
    /**
     * DB parameters
     */
    protected array $options;
    /**
     * Whether to log query analysis to a file
     */
    protected bool $explain;
    /**
     * Explain prefix
     */
    protected string $explainPrefix;
    /**
     * Path to the file where explain.xls is located
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
        $quoteSign = $this->getQuoteSign();
        $this->whereBuilder->setQuoteSign($quoteSign);
        $this->updateBuilder->setQuoteSign($quoteSign);
        $this->insertBuilder->setQuoteSign($quoteSign);
        if ($this->explain = $this->options['explain'] ?? $this->env->isDevelop()) {
            $this->explainPath = $this->options['explain_path'] ?? APP_ROOT . '/runtime/explain.xls';
            // delete file
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
     * Returns the DBMS quote sign
     */
    abstract protected function getQuoteSign(): string;

    /**
     * Surrounds $tblName with quote marks
     */
    protected function quoteTblName($tblName): string
    {
        $quoteSign = $this->getQuoteSign();
        return "$quoteSign$tblName$quoteSign";
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
     * @param string $tblName table name
     * @param array  $where search condition
     * @param array  $fields field names
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
     * Extracts fields $fields of the record from table $tblName by condition $where
     *
     * @param Query  $query
     * @param string $tblName table name
     * @param array  $where search condition
     * @param array  $fields field names
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
     * Inserts records with values $fields into table $tblName
     *
     * @param Query  $query
     * @param string $tblName table name
     * @param array  $fields array: [names => values] of fields
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
            INSERT INTO {$this->quoteTblName($tblName)}
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
     * Updates table $tblName fields $fields of records by condition $where
     *
     * @param Query  $query
     * @param string $tblName table name
     * @param array  $fields  array: [names => values] of fields
     * @param array  $where   search condition
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
     * Deletes records from table $tblName by condition $where
     *
     * @param Query $query
     * @param string $tblName table name
     * @param array  $where search condition
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
            DELETE FROM {$this->quoteTblName($tblName)}
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
        $stmt = $this->connection->prepare("TRUNCATE {$this->quoteTblName($tblName)}");

        return $this->execute($stmt);
    }

    /**
     * Executes query $query
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
     * Executes query $query and returns result as array of records
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
     * Executes query $query and returns a single record as an array
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
     * Initiates a transaction
     *
     * @throws DBALException
     */
    public function beginTransaction(): void
    {
        $this->connect();
        $this->connection->beginTransaction();
    }

    /**
     * Commits/ends a transaction
     */
    public function endTransaction(): void
    {
        $this->connection->commit();
    }

    # HELPER METHODS

    /**
     * Order by cast string
     */
    abstract public function orderByCast(string $colName): string;

    protected function getPlaceholder(array $fields): string
    {
        $placeholderArr = array_fill(0, count($fields), '?');
        return implode(',', $placeholderArr);
    }

    /**
     * Returns a single row from the fetched array of rows
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
	 * Preparing the fetched array of rows, removing unnecessary elements
	 */
	protected function prepareRows(array $rows = []): array
	{
		foreach ($rows as &$row) {
			$row = $this->prepareRow($row);
		}
		return $rows;
	}

	/**
	 * Preparing the fetched row, removing unnecessary elements
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
     * Query execution
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
     * Generates EXPLAIN report
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
            // output to file
            $file = fopen($this->explainPath, 'w');
            fwrite($file, $output);
            fclose($file);
        } catch (Exception $e) {
            throw new DBALException($e->getMessage());
        }
    }
}
