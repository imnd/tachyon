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
     * Marks newly created entity as new.
     */
    public function markNew(): self;

    /**
     * Marks entity as dirty (modified).
     */
    public function markDirty(): self;

    /**
     * Marks entity for deletion.
     */
    public function markDeleted(): self;
}
