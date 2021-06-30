<?php

namespace tachyon\db\dataMapper;

use Iterator;

interface RepositoryInterface
{
    /**
     * @return string
     */
    public function getTableName(): string;

    /**
     * Устанавливает условия поиска для хранилища
     *
     * @param array $conditions условия поиска
     *
     * @return Repository
     */
    public function setSearchConditions(array $conditions = []): Repository;

    /**
     * Устанавливает условия сортировки для хранилища.
     *
     * @param array $attrs
     *
     * @return Repository
     */
    public function setSort($attrs): Repository;

    /**
     * Добавляет условия сортировки для хранилища к уже существующим.
     *
     * @param string $field
     * @param string $order
     *
     * @return void
     */
    public function addSortBy(string $field, string $order);

    /**
     * Получает сущность по условию $where
     *
     * @param array $where
     *
     * @return null|Entity
     */
    public function findOne(array $where = []): ?Entity;

    /**
     * Получает сущность по условию $where
     *
     * @param array $where
     *
     * @return null|array
     */
    public function findOneRaw(array $where = []): ?array;

    /**
     * Получает все сущности по условию $where, отсортированных по $sort
     * и преобразовывает в Iterator
     *
     * @param array $where
     * @param array $sort
     *
     * @return Iterator
     */
    public function findAll(array $where = [], array $sort = []): Iterator;

    /**
     * Получает все сущности по условию $where, отсортированных по $sort в виде массива
     *
     * @param array $where
     * @param array $sort
     *
     * @return array
     */
    public function findAllRaw(array $where = [], array $sort = []): array;

    /**
     * Получить сущность по первичному ключу.
     *
     * @param mixed $pk
     *
     * @return null|Entity
     */
    public function findByPk($pk): ?Entity;

    /**
     * Создает новую сущность.
     *
     * @param bool $markNew
     *
     * @return null|Entity
     */
    public function create($markNew = true): ?Entity;
}
