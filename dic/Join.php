<?php
namespace tachyon\dic;

/**
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
trait Join
{
    /**
     * @var \tachyon\db\Join $join
     */
    protected $join;

    /**
     * @param \tachyon\db\Join $service
     * @return void
     */
    public function setJoin(\tachyon\db\Join $service)
    {
        $this->join = $service;
    }
}
