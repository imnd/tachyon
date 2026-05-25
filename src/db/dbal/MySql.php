<?php

namespace tachyon\db\dbal;

/**
 * MySql DBAL
 *
 * @author imndsu@gmail.com
 */
class MySql extends Db
{
    protected string $explainPrefix = 'EXPLAIN';

    /** @inheritdoc */
    protected function getDBMS(): string
    {
        return 'MySql';
    }

    /** @inheritdoc */
    protected function getDsn(): string
    {
        $port = $this->options['port'] ?? '3306';
        return "mysql:host={$this->options['host']};port=$port;dbname={$this->options['name']}";
    }

    /** @inheritdoc */
    public function isTableExists(string $tableName): bool
    {
        $this->connect();
        $stmt = $this->connection->prepare("SHOW TABLES LIKE ?");
        $this->execute($stmt, [str_replace('`', '', $tableName)]);
        // if such table exists
        return count($stmt->fetchAll()) > 0;
    }

    # HELPER METHODS

    /** @inheritdoc */
    public function orderByCast(string $colName): string
    {
        return "CAST($colName as unsigned)";
    }
}
