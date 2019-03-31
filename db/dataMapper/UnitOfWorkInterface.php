<?php
namespace tachyon\db\dataMapper;

/**
 * @author Андрей Сердюк
 * @copyright (c) 2019 IMND
 */
interface UnitOfWorkInterface
{
    /**
     * @return DbContext
     */
    public function getDbContext();

    /**
     * Помечает только что созданую сущность как новую.
     */
    public function markNew();

    /**
     * Помечает сущность как измененную.
     */
    public function markDirty();

    /**
     * Помечает сущность на удаление.
     */
    public function markDeleted();
}
