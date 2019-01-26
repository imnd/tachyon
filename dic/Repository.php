<?php
namespace tachyon\dic;

/**
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
trait Repository
{
    /**
     * @var \tachyon\db\dataMapper\Repository
     */
    protected $repository;

    /**
     * @param \tachyon\db\dataMapper\Repository $service
     * @return void
     */
    public function setRepository(\tachyon\db\dataMapper\Repository $service)
    {
        $this->repository = $service;
    }
}
