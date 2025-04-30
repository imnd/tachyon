<?php

namespace tachyon\db\dataMapper;

/**
 * @author imndsu@gmail.com
 */
interface UnitOfWorkInterface
{
    /**
     * @return DbContext
     */
    public function getDbContext(): DbContext;

    /**
     * Помечает только что созданную сущность как новую.
     */
    public function markNew(): self;

    /**
     * Помечает сущность как измененную.
     */
    public function markDirty(): self;

    /**
     * Помечает сущность на удаление.
     */
    public function markDeleted(): self;
}
