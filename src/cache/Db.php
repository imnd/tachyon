<?php
namespace tachyon\cache;

/**
 * class DbCache
 * кеширование содержимого Db
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2020 IMND
 */
class Db extends Cache
{
    /**
     * @inheritdoc
     */
    public function start($cacheKey)
    {
        if (!$this->enabled) {
            return;
        }
        if ($cacheContents = $this->getContents($cacheKey)) {
            return $cacheContents;
        }
        // запускаем кеширование
        $this->setKey($cacheKey);
        ob_start();
    }
    
    /**
     * @inheritdoc
     */
    public function end($contents=null)
    {
        if (!$this->enabled) {
            return;
        }
        $this->save($contents);
    }
}
