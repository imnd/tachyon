<?php

namespace tachyon\db\dataMapper;

use tachyon\exceptions\DBALException;

/**
 * Unit of Work implementation.
 *
 * @author imndsu@gmail.com
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
     * Marks entity as new.
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
     * Marks entity as dirty (modified).
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
     * Marks entity for deletion.
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
     * Flush to DB
     *
     * @throws DBALException
     */
    public function commit(): bool
    {
        $hasChanges = (
               !empty($this->newEntities)
            || !empty($this->dirtyEntities)
            || !empty($this->deletedEntities)
        );
        if ($hasChanges) {
            $this->persistence->beginTransaction();
        }
        $success = true;
        // Saves modified entities to storage
        foreach ($this->dirtyEntities as $entity) {
            $success = $success && $this
                ->persistence
                ->updateByPk(
                    $entity->getPk(),
                    $entity->getAttributes(),
                    $entity->getTableName()
                );
        }
        // Deletes entities from storage
        foreach ($this->deletedEntities as $entity) {
            $success = $success && $this
                ->persistence
                ->deleteByPk(
                    $entity->getPk(),
                    $entity->getTableName()
                );
        }
        // Inserts new entities into storage
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
        if ($hasChanges) {
            $this->persistence->endTransaction();
        }

        return $success;
    }

    /**
     * Flushes a single entity to DB
     *
     * @param Entity $entity
     *
     * @return bool
     * @throws DBALException
     */
    public function saveEntity(Entity $entity): bool
    {
        $success = true;
        $hash = spl_object_hash($entity);

        if (isset($this->dirtyEntities[$hash])) {
            // Saves modified entity to storage
            $success = $success && $this
                    ->persistence
                    ->updateByPk(
                        $entity->getPk(),
                        $entity->getAttributes(),
                        $entity->getTableName()
                    );

            unset($this->dirtyEntities[$hash]);
        } elseif (isset($this->newEntities[$hash])) {
            // Inserts new entity into storage
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
            unset($this->newEntities[$hash]);
        }

        return $success;
    }
}
