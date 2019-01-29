<?php
namespace tachyon\dic;

/**
 * Трэйт сеттера кеша
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
trait OutputCache
{
    /**
     * @var \tachyon\cache\Output $cache
     */
    protected $cache;

    /**
     * @param \tachyon\cache\Output $service
     * @return void
     */
    public function setCache(\tachyon\cache\Output $service)
    {
        $this->cache = $service;
    }
}
