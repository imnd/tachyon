<?php
namespace tachyon\components\cache;

/**
 * class OutputCache
 * кеширование содержимого страницы целиком
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
class Output extends Cache
{
    /**
     * @inheritdoc
     */
    public function start($cacheKey)
    {
        if (!$this->turnedOn)
            return;
            
        if ($cacheContents = $this->getContents($cacheKey)) {
            echo $cacheContents;
            die;
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
        if (!$this->turnedOn)
            return;
        
        if (is_null($contents))
            $contents = ob_get_contents();

        $this->save($contents);
    }
    
    /**
     * @inheritdoc
     */
    protected function save($contents)
    {
        $this->getCacheFilePath();
        file_put_contents($this->cacheFile, $contents);
    }
}
