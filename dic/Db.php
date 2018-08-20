<?php
namespace tachyon\dic;

/**
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
trait Db
{
    /**
     * @var \tachyon\db\Db $db
     */
    protected $db;

    /**
     * @param \tachyon\db\Db $service
     * @return void
     */
    public function setDb(\tachyon\db\Db $service)
    {
        $this->db = $service;
    }

    /**
     * @return \tachyon\db\Db
     */
    public function getDb()
    {
        if (is_null($this->db)) {
            $this->db = \tachyon\dic\Container::getInstanceOf('Db');
        }
        return $this->db;
    }
}
