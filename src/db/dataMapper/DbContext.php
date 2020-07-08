<?php
namespace tachyon\db\dataMapper;

use tachyon\db\dataMapper\Persistence;

/**
 * Реализация Unit of work.
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2020 IMND
 */
class DbContext
{
    /**
     * @var \tachyon\db\dataMapper\Persistence
     */
    protected $persistence;

    /**
     * @var array $newEntities
     */
    private $newEntities = array();
    /**
     * @var array $dirtyEntities
     */
    private $dirtyEntities = array();
    /**
     * @var array $deletedEntities
     */
    private $deletedEntities = array();

    public function __construct(Persistence $persistence)
    {
        $this->persistence = $persistence;
        $this->persistence->setOwner($this);
    }

    /**
     * Помечает сущность как новую.
     * 
     * @param Entity $entity
     * @return void
     */
    public function registerNew(Entity $entity)
    {
        $this->newEntities[spl_object_hash($entity)] = $entity;
    }

    /**
     * Помечает сущность как измененную.
     * 
     * @param Entity $entity
     * @return void
     */
    public function registerDirty(Entity $entity)
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
     * @return void
     */
    public function registerDeleted(Entity $entity)
    {
        if ($this->isNew($entity)) {
            return;
        }
        $this->deletedEntities[spl_object_hash($entity)] = $entity;
    }

    /**
     * @param Entity $entity
     * @return bool
     */
    public function isNew(Entity $entity)
    {
        return isset($this->newEntities[spl_object_hash($entity)]);
    }

    /**
     * @param Entity $entity
     * @return bool
     */
    public function isDirty(Entity $entity)
    {
        return isset($this->dirtyEntities[spl_object_hash($entity)]);
    }

    /**
     * @param Entity $entity
     * @return bool
     */
    public function isDeleted(Entity $entity)
    {
        return isset($this->deletedEntities[spl_object_hash($entity)]);
    }

    /**
     * Сливаем в БД
     */
    public function commit()
    {
        if (
               !empty($this->newEntities)
            || !empty($this->dirtyEntities)
            || !empty($this->deletedEntities)
        ) {
            $this->persistence->beginTransaction();
        }
        $success = true;
        // Сохраняет в хранилище измененную сущность
        foreach ($this->dirtyEntities as $entity) {
            $success = $success && $this
                ->persistence
                ->updateByPk($entity->getPk(), $entity->getAttributes(), $entity->getTableName());
        }
        // Удаляет сущность из хранилища
        foreach ($this->deletedEntities as $entity) {
            $success = $success && $this
                ->persistence
                ->deleteByPk($entity->getPk(), $entity->getTableName());
        }
        // Вставляет в хранилище новую сущность
        foreach ($this->newEntities as &$entity) {
            if (!$pk = $this
                ->persistence
                ->insert($entity->getAttributes(), $entity->getTableName())) {
                $success = false;
            }
            $entity->setPk($pk);
        }
        $this->newEntities = $this->dirtyEntities = $this->deletedEntities = array();

        $this->persistence->endTransaction();

        return $success;
    }
}
