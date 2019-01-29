<?php
namespace tachyon\dic;

/**
 * @author Андрей Сердюк
 * @copyright (c) 2019 IMND
 */
trait DbContext
{
    /**
     * @var \tachyon\db\dataMapper\DbContext $dbContext
     */
    protected $dbContext;

    /**
     * @param \tachyon\db\dataMapper\DbContext $service
     * @return void
     */
    public function setDbContext(\tachyon\db\dataMapper\DbContext $service)
    {
        $this->dbContext = $service;
    }
}
