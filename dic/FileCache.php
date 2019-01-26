<?php
namespace tachyon\dic;

/**
 * Трэйт сеттера кеша
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
trait FileCache
{
    /**
     * @var \tachyon\components\cache\File $cache
     */
    protected $cache;

    /**
     * @param \tachyon\components\cache\File $service
     * @return void
     */
    public function setCache(\tachyon\components\cache\File $service)
    {
        $this->cache = $service;
    }
}
