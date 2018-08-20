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
            
        if ($cacheContents = $this->get($cacheKey)) {
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
    protected function getContents()
    {
        ob_start();
        require($this->cacheFile);
        $contents = ob_get_contents();
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
