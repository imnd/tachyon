<?php
namespace tachyon\dic;

/**
 * Трэйт сеттера кеша
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
trait DbCache
{
    /**
     * @var \tachyon\cache\Db $cache
     */
    protected $cache;

    /**
     * @param \tachyon\cache\Db $service
     * @return void
     */
    public function setCache(\tachyon\cache\Db $service)
    {
        $this->cache = $service;
    }
}
