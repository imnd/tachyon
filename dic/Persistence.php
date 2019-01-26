<?php
namespace tachyon\dic;

/**
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
trait Persistence
{
    /**
     * @var \tachyon\db\dataMapper\Persistence
     */
    protected $persistence;

    /**
     * @param \tachyon\db\dataMapper\Persistence $service
     * @return void
     */
    public function setPersistence(\tachyon\db\dataMapper\Persistence $service)
    {
        $this->persistence = $service;
    }
}
