<?php

namespace tachyon\db\dbal;

/**
 * PostgreSQL DBAL
 *
 * @author Андрей Сердюк
 * @copyright (c) 2020 IMND
 */
class PgSql extends Db
{
    protected string $explainPrefix = 'EXPLAIN EXECUTE';

    /**
     * @inheritdoc
     */
    protected function getDsn(): string
    {
        $port = $this->options['port'] ?? '5432';
        return "pgsql:host={$this->options['host']};port=$port;dbname={$this->options['name']}";
    }

    /**
     * @inheritdoc
     */
    public function isTableExists(string $tableName): bool
    {
        $this->connect();
        $stmt = $this->connection->prepare("SELECT * FROM pg_catalog.pg_tables");
        $this->execute($stmt, [str_replace('`', '', $tableName)]);
        // если такая таблица существует
        return count($stmt->fetchAll()) > 0;
    }

    # ВСПОМОГАТЕЛЬНЫЕ МЕТОДЫ

    /**
     * @inheritdoc
     */
    public function orderByCast(string $colName): string
    {
        return "$colName::int";
    }

    /**
     * @param $field
     *
     * @return string
     */
    protected function prepareField($field): string
    {
        if (preg_match('/[.( ]/', $field) === 0) {
            $field = trim($field);
        }
        return $field;
    }
}
