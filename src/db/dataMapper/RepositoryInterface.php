<?php

namespace tachyon\db\dataMapper;

use Iterator;

interface RepositoryInterface
{
    public function getTableName(): string;

    /**
     * Sets search conditions for storage
     *
     * @param array $conditions search conditions
     */
    public function setSearchConditions(array $conditions = []): Repository;

    /**
     * Sets sorting conditions for storage.
     */
    public function setSort(array $attrs): Repository;

    /**
     * Adds sorting conditions for storage to existing ones.
     */
    public function addSortBy(string $orderBy, string $order);

    /**
     * Gets the entity by condition $where
     */
    public function findOne(array $where = []): ?Entity;

    /**
     * Gets the entity by condition $where
     */
    public function findOneRaw(array $where = []): ?array;

    /**
     * Gets all entities by condition $where sorted by $sort
     * and converts to Iterator
     */
    public function findAll(array $where = [], array $sort = []): Iterator;

    /**
     * Gets all entities by condition $where sorted by $sort as array
     */
    public function findAllRaw(array $where = [], array $sort = []): array;

    /**
     * Get entity by primary key.
     */
    public function findByPk(mixed $pk): ?Entity;

    /**
     * Creates a new entity.
     */
    public function create(bool $markNew = true): ?Entity;
}
