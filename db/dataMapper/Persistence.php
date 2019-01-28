<?php
namespace tachyon\db\dataMapper;

class Persistence extends \tachyon\Component
{
    use \tachyon\dic\DbFactory;

    public function findByPk($pk)
    {
        return $this->dbFactory->getDb()->selectById($this->domain->getTableName(), $pk);
    }

    public function findAll(array $condition = array()): array
    {
        return $this->dbFactory->getDb()->select($this->domain->getTableName(), $condition);
    }

    public function findAllBySql(string $sql): array
    {
        return $this->dbFactory->getDb()->queryAll($sql);
    }
}
