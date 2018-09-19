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
     * @var \tachyon\components\cache\Output $cache
     */
    protected $cache;

    /**
     * @param \tachyon\components\cache\Output $service
     * @return void
     */
    public function setCache(\tachyon\components\cache\Output $service)
    {
        $this->cache = $service;
    }
}
