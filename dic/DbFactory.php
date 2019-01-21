<?php
namespace tachyon\dic;

/**
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
trait DbFactory
{
    /**
     * @var \tachyon\db\dbal\DbFactory
     */
    protected $dbFactory;

    /**
     * @param \tachyon\db\dbal\Db $service
     * @return void
     */
    public function setDbFactory(\tachyon\db\dbal\DbFactory $service)
    {
        $this->dbFactory = $service;
    }
}
