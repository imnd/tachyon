<?php
namespace tachyon\db\dataMapper;

class Persistence extends \tachyon\Component
{
    use \tachyon\dic\DbFactory;

    protected $tableName;

    /**
     * @param string $tableName
     * @return void
     */
    public function setTableName(string $tableName)
    {
        $this->tableName = $tableName;
    }

    public function findAll(array $condition = array()): array
    {
        return $this->dbFactory->getDb()->select($this->tableName, $condition);
    }

    public function findAllBySql(string $sql): array
    {
        return $this->dbFactory->getDb()->queryAll($sql);
    }
}
