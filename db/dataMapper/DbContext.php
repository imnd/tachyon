<?php
namespace tachyon\db\dataMapper;

/**
 * Реализация Unit of work.
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2019 IMND
 */
class DbContext extends \tachyon\Component
{
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
        $success = true;
        foreach ($this->newEntities as $entity) {
            $success = $success && $entity->getRepository()->insert($entity);
        }
        foreach ($this->dirtyEntities as $entity) {
            $success = $success && $entity->getRepository()->update($entity);
        }
        foreach ($this->deletedEntities as $entity) {
            $success = $success && $entity->getRepository()->delete($entity);
        }
        return $success;
    }
}
