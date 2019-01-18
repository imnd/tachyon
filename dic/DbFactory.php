<?php
namespace tachyon\dic;

/**
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
trait DbFactory
{
    /**
     * @var \tachyon\db\DbFactory
     */
    protected $dbFactory;

    /**
     * @param \tachyon\db\Db $service
     * @return void
     */
    public function setDbFactory(\tachyon\db\DbFactory $service)
    {
        $this->dbFactory = $service;
    }
}
