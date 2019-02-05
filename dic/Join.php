<?php
namespace tachyon\dic;

/**
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
trait Join
{
    /**
     * @var \tachyon\db\activeRecord\Join $join
     */
    protected $join;

    /**
     * @param \tachyon\db\activeRecord\Join $service
     * @return void
     */
    public function setJoin(\tachyon\db\activeRecord\Join $service)
    {
        $this->join = $service;
    }
}
