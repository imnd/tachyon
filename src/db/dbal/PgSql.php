<?php

namespace tachyon\db\dbal;

use Exception;
use tachyon\exceptions\DBALException;

/**
 * PostgreSQL DBAL
 *
 * @author Андрей Сердюк
 * @copyright (c) 2020 IMND
 */
class PgSql extends Db
{
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

    /**
     * @inheritdoc
     */
    protected function explain(
        string $query,
        array  $conditions1,
        array  $conditions2 = null
    ): void
    {
        $query = trim(preg_replace('!\s+!', ' ', str_replace(["\r", "\n"], ' ', $query)));
        $output = "query: $query\r\nid\tselect_type\ttable\ttype\tpossible_keys\tkey\tkey_len\tref\trows\tExtra\r\n";
        $fields = $conditions1['vals'];
        if (!is_null($conditions2)) {
            $fields = array_merge($fields, $conditions2['vals']);
        }
        // выводим в файл
        $stmt = $this->connection->prepare("EXPLAIN EXECUTE $query");
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
            $file = fopen($this->explainPath, "w");
            fwrite($file, $output);
            fclose($file);
        } catch (Exception $e) {
            throw new DBALException($e->getMessage());
        }
    }
}
