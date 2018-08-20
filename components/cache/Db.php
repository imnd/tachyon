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

        if ($cacheContents = $this->get($cacheKey))
            return $cacheContents;

        // запускаем кеширование
        $this->setKey($cacheKey);
        ob_start();
    }
    
    /**
     * @inheritdoc
     */
    protected function getContents()
    {
        ob_start();
        require($this->cacheFile);
        $contents = ob_get_contents();
        $contents = unserialize($contents);
        ob_end_clean();
        return $contents;
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
