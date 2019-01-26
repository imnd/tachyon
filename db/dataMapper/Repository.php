<?php
namespace tachyon\db\dataMapper;

use tachyon\helpers\StringHelper;

/**
 * EntityManager является центральной точкой доступа к функциональности DataMapper ORM.
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2019 IMND
 */
abstract class Repository extends \tachyon\Component
{
    use \tachyon\dic\Persistence;

    /** @var string */
    protected $tableName;
    /**
     * Имя класса сущности
     */
    protected $entityName;

    public function initialize()
    {
        $this->persistence->setTableName($this->tableName);
        if (is_null($this->entityName)) {
            $this->entityName = str_replace('Repository', '', StringHelper::getShortClassName(get_called_class()));
        }
    }

    /**
     * @param string $tableName
     * @return void
     */
    public function getTableName()
    {
        return $this->tableName;
    }

    /**
     * Получить все записи 
     */
    public function findAll(): array
    {
        $arrayData = $this->persistence->findAll();
        $entities = array();
        foreach ($arrayData as $data) {
            $entities[] = $this->{$this->entityName}->fromState($data);
        }
        return $entities;
    }
}
