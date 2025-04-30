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

    /**
     * @inheritdoc
     */
    protected function getDsn(): string
    {
        $port = $this->options['port'] ?? '3306';
        return "mysql:host={$this->options['host']};port=$port;dbname={$this->options['name']}";
    }

    /**
     * @inheritdoc
     */
    public function isTableExists(string $tableName): bool
    {
        $this->connect();
        $stmt = $this->connection->prepare("SHOW TABLES LIKE ?");
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
        return "CAST($colName as unsigned)";
    }
}
