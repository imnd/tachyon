<?php

namespace tachyon\db\dataMapper;

use Iterator;

interface RepositoryInterface
{
    public function getTableName(): string;

    /**
     * Устанавливает условия поиска для хранилища
     *
     * @param array $conditions условия поиска
     */
    public function setSearchConditions(array $conditions = []): Repository;

    /**
     * Устанавливает условия сортировки для хранилища.
     */
    public function setSort(array $attrs): Repository;

    /**
     * Добавляет условия сортировки для хранилища к уже существующим.
     */
    public function addSortBy(string $orderBy, string $order);

    /**
     * Получает сущность по условию $where
     */
    public function findOne(array $where = []): ?Entity;

    /**
     * Получает сущность по условию $where
     */
    public function findOneRaw(array $where = []): ?array;

    /**
     * Получает все сущности по условию $where, отсортированных по $sort
     * и преобразовывает в Iterator
     */
    public function findAll(array $where = [], array $sort = []): Iterator;

    /**
     * Получает все сущности по условию $where, отсортированных по $sort в виде массива
     */
    public function findAllRaw(array $where = [], array $sort = []): array;

    /**
     * Получить сущность по первичному ключу.
     */
    public function findByPk(mixed $pk): ?Entity;

    /**
     * Создает новую сущность.
     */
    public function create(bool $markNew = true): ?Entity;
}
