<?php

namespace tachyon\db\dbal;

/**
 * PostgreSQL DBAL
 *
 * @author imndsu@gmail.com
 */
class PgSql extends Db
{
    protected string $explainPrefix = 'EXPLAIN EXECUTE';

    /** @inheritdoc */
    protected function getQuoteSign(): string
    {
        return '"';
    }

    /** @inheritdoc */
    protected function getDsn(): string
    {
        $port = $this->options['port'] ?? '5432';
        return "pgsql:host={$this->options['host']};port=$port;dbname={$this->options['name']}";
    }

    /** @inheritdoc */
    public function isTableExists(string $tableName): bool
    {
        $this->connect();
        $stmt = $this->connection->prepare("SELECT * FROM pg_catalog.pg_tables");
        $this->execute($stmt, [str_replace('`', '', $tableName)]);
        // if such table exists
        return count($stmt->fetchAll()) > 0;
    }

    # HELPER METHODS

    /** @inheritdoc */
    public function orderByCast(string $colName): string
    {
        return "$colName::int";
    }

    protected function prepareField(string $field): string
    {
        if (preg_match('/[.( ]/', $field) === 0) {
            $field = trim($field);
        }
        return $field;
    }
}
