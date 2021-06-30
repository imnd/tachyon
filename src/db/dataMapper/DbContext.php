<?php

namespace tachyon\db\dataMapper;

use tachyon\exceptions\DBALException;

/**
 * Реализация Unit of work.
 *
 * @author Андрей Сердюк
 * @copyright (c) 2020 IMND
 */
class DbContext
{
    /**
     * @var Persistence
     */
    protected Persistence $persistence;

    /**
     * @var array $newEntities
     */
    private array $newEntities = [];
    /**
     * @var array $dirtyEntities
     */
    private $dirtyEntities = [];
    /**
     * @var array $deletedEntities
     */
    private $deletedEntities = [];

    public function __construct(Persistence $persistence)
    {
        $this->persistence = $persistence;
        $this->persistence->setOwner($this);
    }

    /**
     * Помечает сущность как новую.
     *
     * @param Entity $entity
     *
     * @return void
     */
    public function registerNew(Entity $entity): void
    {
        $this->newEntities[spl_object_hash($entity)] = $entity;
    }

    /**
     * Помечает сущность как измененную.
     *
     * @param Entity $entity
     *
     * @return void
     */
    public function registerDirty(Entity $entity): void
    {
        if ($this->isNew($entity)) {
            return;
        }
        $this->dirtyEntities[spl_object_hash($entity)] = $entity;
    }

    /**
     * Помечает сущность на удаление.
     *
     * @param Entity $entity
     *
     * @return void
     */
    public function registerDeleted(Entity $entity): void
    {
        if ($this->isNew($entity)) {
            return;
        }
        $this->deletedEntities[spl_object_hash($entity)] = $entity;
    }

    /**
     * @param Entity $entity
     *
     * @return bool
     */
    public function isNew(Entity $entity): bool
    {
        return isset($this->newEntities[spl_object_hash($entity)]);
    }

    /**
     * @param Entity $entity
     *
     * @return bool
     */
    public function isDirty(Entity $entity): bool
    {
        return isset($this->dirtyEntities[spl_object_hash($entity)]);
    }

    /**
     * @param Entity $entity
     *
     * @return bool
     */
    public function isDeleted(Entity $entity): bool
    {
        return isset($this->deletedEntities[spl_object_hash($entity)]);
    }

    /**
     * Сливаем в БД
     *
     * @throws DBALException
     */
    public function commit(): bool
    {
        if (
               !empty($this->newEntities)
            || !empty($this->dirtyEntities)
            || !empty($this->deletedEntities)
        ) {
            $this->persistence->beginTransaction();
        }
        $success = true;
        // Сохраняет в хранилище измененные сущности
        foreach ($this->dirtyEntities as $entity) {
            $success = $success && $this
                ->persistence
                ->updateByPk(
                    $entity->getPk(),
                    $entity->getAttributes(),
                    $entity->getTableName()
                );
        }
        // Удаляет сущности из хранилища
        foreach ($this->deletedEntities as $entity) {
            $success = $success && $this
                ->persistence
                ->deleteByPk(
                    $entity->getPk(),
                    $entity->getTableName()
                );
        }
        // Вставляет в хранилище новые сущности
        foreach ($this->newEntities as &$entity) {
            if (!$pk = $this
                ->persistence
                ->insert(
                    $entity->getAttributes(),
                    $entity->getTableName()
                )
            ) {
                $success = false;
            }
            $entity->setPk($pk);
        }
        unset($entity);
        $this->newEntities = $this->dirtyEntities = $this->deletedEntities = [];
        $this->persistence->endTransaction();

        return $success;
    }

    /**
     * Сливаем в БД одну сущность
     *
     * @param Entity $entity
     *
     * @return bool
     * @throws DBALException
     */
    public function saveEntity(Entity $entity): bool
    {
        $success = true;

        if (in_array($entity, $this->dirtyEntities)) {
            // Сохраняет в хранилище измененную сущность
            $success = $success && $this
                    ->persistence
                    ->updateByPk(
                        $entity->getPk(),
                        $entity->getAttributes(),
                        $entity->getTableName()
                    );

            unset($this->dirtyEntities[array_search($entity, $this->dirtyEntities)]);
        } elseif (in_array($entity, $this->newEntities)) {
            // Вставляет в хранилище новую сущность
            if (!$pk = $this
                ->persistence
                ->insert(
                    $entity->getAttributes(),
                    $entity->getTableName()
                )
            ) {
                $success = false;
            }
            $entity->setPk($pk);
            unset($this->newEntities[array_search($entity, $this->newEntities)]);
        }

        return $success;
    }
}
