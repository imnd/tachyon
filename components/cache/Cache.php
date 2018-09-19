<?php
namespace tachyon\components\cache;

/**
 * кеширование
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
abstract class Cache extends \tachyon\Component
{
    use \tachyon\dic\Config;

    protected $duration = 60;
    protected $cacheFolder = '../runtime/cache/';
    protected $cacheFile = '';
    protected $key = '';
    protected $turnedOn = false;

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

        $this->turnedOn = false;
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
    abstract protected function save($contents);

    /**
     * заканчиваем кеширование (слив содержимого вывода в файл)
     * @param string $contents
     */
    abstract public function end($contents = null);

    protected function getContents($key, $unserialize = false)
    {
        $this->setKey($key);
        $this->getCacheFilePath();
        if (file_exists($this->cacheFile)) {
            $modifTime = filemtime($this->cacheFile);
            $time = time();
            $age = $time - $modifTime;
            if ($this->duration < $age)
                return;
            
            ob_start();
            require($this->cacheFile);
            $contents = ob_get_contents();
            if ($unserialize)
                $contents = unserialize($contents);

            ob_end_clean();
            return $contents;
        }
        return;
    }

    protected function setKey($key)
    {
        $this->key = md5($key);
    }

    protected function getCacheFilePath()
    {
        $this->cacheFile = "{$this->cacheFolder}{$this->key}.php";
    }
}
