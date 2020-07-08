<?php
namespace tachyon\cache;

/**
 * кеширование содержимого страницы целиком
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2020 IMND
 */
class Output extends Cache
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
        if (!$this->enabled) {
            return;
        }
        if (is_null($contents)) {
            $contents = ob_get_contents();
        }
        $this->save($contents);
    }
}
