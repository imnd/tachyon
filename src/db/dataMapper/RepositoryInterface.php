<?php
namespace tachyon\db\dataMapper;

use Iterator;

interface RepositoryInterface
{
    /**
     * @return string
     */
    public function getTableName();

    /**
     * Устанавливает условия поиска для хранилища
     * 
     * @param array $conditions условия поиска
     * @return Repository
     */
    public function setSearchConditions(array $conditions = array());

    /**
     * Устанавливает условия сортировки для хранилища.
     * 
     * @param array $attrs
     * @return Repository
     */
    public function setSort($attrs);

    /**
     * Добавляет условия сортировки для хранилища к уже существующим.
     * 
     * @param string $field
     * @param string $order
     * @return void
     */
    public function addSortBy($field, $order);

    /**
     * Получает сущность по условию $where
     * 
     * @param array $where
     * @return null|Entity
     */
    public function findOne(array $where = array()): ?Entity;

    /**
     * Получает сущность по условию $where
     * 
     * @param array $where
     * @return null|Entity
     */
    public function findOneRaw(array $where = array()): ?Entity;

    /**
     * Получает все сущности по условию $where, отсортированных по $sort
     * и преобразовывает в Iterator
     * 
     * @param array $where
     * @param array $sort
     * @return Iterator
     */
    public function findAll(array $where = array(), array $sort = array()): Iterator;

    /**
     * Получает все сущности по условию $where, отсортированных по $sort в виде массива
     *
     * @param array $where
     * @param array $sort
     * @return Iterator
     */
    public function findAllRaw(array $where = array(), array $sort = array()): Iterator;

    /**
     * Получить сущность по первичному ключу.
     * 
     * @param mixed $pk
     * @return null|Entity
     */
    public function findByPk($pk): ?Entity;

    /**
     * Создает новую сущность.
     *
     * @param bool $mark
     * @return null|Entity
     */
    public function create($mark = true): ?Entity;
}
