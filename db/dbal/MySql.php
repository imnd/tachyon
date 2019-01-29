<?php
namespace tachyon\db\dbal;

/**
 * MySql DBAL
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2019 IMND
 */
class MySql extends Db
{
    /**
     * @inheritdoc
     */
    protected function getDsn(): string
    {
        return "mysql:host={$this->config['host']};dbname={$this->config['dbname']}";
    }

    /**
     * @inheritdoc
     */
    public function isTableExists(string $tableName): bool
    {
        $this->connect();

        $stmt = $this->connection->prepare("SHOW TABLES LIKE ?");
        $this->execute($stmt, array(str_replace('`', '', $tableName)));
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

    /**
     * выдает отчет EXPLAIN
     */
    protected function explain($query, $conditions1, $conditions2=null)
    {
        $query = trim(preg_replace('!\s+!', ' ', str_replace(array("\r", "\n"), ' ', $query)));
        $output = "query: $query\r\nid\tselect_type\ttable\ttype\tpossible_keys\tkey\tkey_len\tref\trows\tExtra\r\n";

        $fields = $conditions1['vals'];
        if (!is_null($conditions2)) {
            $fields = array_merge($fields, $conditions2['vals']);
        }
        // выводим в файл
        $stmt = $this->connection->prepare("EXPLAIN $query");
        try {
            $this->execute($stmt, $fields);
            $rows = $stmt->fetchAll();
            foreach ($rows as $row) {
                foreach ($row as $key => $value)
                    if (is_numeric($key))
                        $output .= "$value\t";

                $output .= "\r\n";
            }
            $file = fopen(self::$explainPath, "w");
            fwrite($file, $output);
            fclose($file);
        } catch (\Exception $e) {
            throw new \tachyon\exceptions\DBALException('Some error occured');
        }
    }
}
