<?php
namespace tachyon\components\cache;

/**
 * кеширование содержимого в файл
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
class File extends Cache
{
    protected $cacheFolder = '../runtime/cache/';
    protected $cacheFile = '';

    /**
     * слив содержимого вывода в файл
     * @param string $contents
     */
    protected function save($contents)
    {
        $this->getCacheFilePath();
        if ($this->serialize) {
            $contents = serialize($contents);
        }
        file_put_contents($this->cacheFile, $contents);
    }

    protected function getContents($key)
    {
        $this->setKey($key);
        $this->getCacheFilePath();
        if (file_exists($this->cacheFile)) {
            $modifTime = filemtime($this->cacheFile);
            $time = time();
            $age = $time - $modifTime;
            if ($this->duration < $age) {
                return;
            }
            ob_start();
            require($this->cacheFile);
            $contents = ob_get_contents();
            if ($this->serialize) {
                $contents = unserialize($contents);
            }
            ob_end_clean();
            return $contents;
        }
        return;
    }

    protected function getCacheFilePath()
    {
        $this->cacheFile = "{$this->cacheFolder}{$this->key}.php";
    }
}
