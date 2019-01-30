<?php
namespace tachyon\cache;

/**
 * кеширование
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
abstract class Cache extends \tachyon\Component
{
    protected $duration = 60;
    protected $cacheFolder = '../runtime/cache/';
    protected $cacheFile = '';
    protected $key = '';
    protected $enabled = false;
    protected $serialize = false;

    /**
     * Инициализация
     * @return void
     */
    public function __construct()
    {
        $type = strtolower($this->getClassName());
        $cache = $this->get('config')->getOption('cache');
        if ($this->get('config')->getOption('mode')!=='production' || !isset($cache[$type]))
            return;

        $options = $cache[$type];
        foreach ($options as $key => $value)
            if (property_exists($type, $key))
                $this->$key = $value;
    }

    /**
     * возвращает содержимое файла кэша или включает буфферинг вывода
     * @param string $cacheKey
     */
    abstract public function start($cacheKey);

    /**
     * заканчиваем кеширование (слив содержимого вывода в файл)
     * @param string $contents
     */
    abstract public function end($contents = null);

    protected function getContents($key)
    {
        $this->setKey($key);
        $this->setCacheFilePath();
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

    /*protected function getContents()
    {
        ob_start();
        require($this->cacheFile);
        $contents = ob_get_contents();
        if ($this->serialize) {
            $contents = unserialize($contents);
        }
        ob_end_clean();
        return $contents;
    }*/

    protected function setKey($key)
    {
        $this->key = md5($key);
    }
    
    /**
     * @inheritdoc
     */
    protected function save($contents)
    {
        $this->setCacheFilePath();
        if ($this->serialize) {
            $contents = serialize($contents);
        }
        file_put_contents($this->cacheFile, $contents);
    }

    protected function setCacheFilePath()
    {
        $this->cacheFile = "{$this->cacheFolder}{$this->key}.php";
    }
}