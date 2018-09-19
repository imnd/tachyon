<?php
namespace tachyon\components\cache;

/**
 * class DbCache
 * кеширование содержимого Db
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
class Db extends Cache
{
    /**
     * @inheritdoc
     */
    public function start($cacheKey)
    {
        if (!$this->turnedOn)
            return;

        if ($cacheContents = $this->getContents($cacheKey, true))
            return $cacheContents;

        // запускаем кеширование
        $this->setKey($cacheKey);
        ob_start();
    }
    
    /**
     * @inheritdoc
     */
    public function end($contents=null)
    {
        if (!$this->turnedOn)
            return;

        $this->save($contents);
    }
    
    /**
     * @inheritdoc
     */
    protected function save($contents)
    {
        $this->getCacheFilePath();
        file_put_contents($this->cacheFile, serialize($contents));
    }
}
