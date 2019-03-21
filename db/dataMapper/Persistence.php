<?php
namespace tachyon\db\dataMapper;

use tachyon\db\dbal\DbFactory;

class Persistence
{
    use \tachyon\traits\HasOwner;

    /**
     * @var \tachyon\db\dbal\DbFactory
     */
    protected $dbFactory;

    /**
     * @return void
     */
    public function __construct(DbFactory $dbFactory)
    {
        $this->dbFactory = $dbFactory;
    }

    /**
     * Находит все записи по условию $condition
     */
    public function findAll(array $condition = array(), array $sort = array()): array
    {
        $db = $this->dbFactory->getDb();
        if (!empty($sort)) {
            foreach ($sort as $fieldName => $order) {
                $db->orderBy($fieldName, $order);
            }
        }
        return $db->select($this->owner->getTableName(), $condition);
    }

    /**
     * Находит запись по первичному ключу
     * 
     * return mixed;
     */
    public function findByPk($pk)
    {
        return $this->dbFactory->getDb()->selectOne($this->owner->getTableName(), ['id' => $pk]);
    }

    /**
     * Обновляет запись по первичному ключу
     * 
     * @return boolean
     */
    public function updateByPk($pk, array $fieldValues)
    {
        return $this->dbFactory->getDb()->update($this->owner->getTableName(), $fieldValues, ['id' => $pk]);
    }

    /**
     * Сохраняет запись в хранилище
     * 
     * @return boolean
     */
    public function insert(array $fieldValues)
    {
        return $this->dbFactory->getDb()->insert($this->owner->getTableName(), $fieldValues);
    }

    /**
     * Удаляет запись из хранилища
     *
     * @param mixed $pk
     * @return boolean
     */
    public function deleteByPk($pk)
    {
        return $this->dbFactory->getDb()->delete($this->owner->getTableName(), ['id' => $pk]);
    }
}
