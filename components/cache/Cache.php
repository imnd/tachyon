<?php
namespace tachyon\components\cache;

/**
 * class Cache
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
        $cache = $this->getConfig()->getOption('cache');
        if ($this->getConfig()->getOption('mode')!=='production' || !isset($cache[$type]))
            return;
            
        $this->turnedOn = false;
        $options = $cache[$type];
        foreach ($options as $key => $value)
            if (property_exists($type, $key))
                $this->$key = $value;
    }

    /**
     * возвращает содержимое файла кэша
     * @return string
     */
    abstract protected function getContents();

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

    public function get($key)
    {
        $this->setKey($key);
        $this->getCacheFilePath();
        if (file_exists($this->cacheFile)) {
            $modifTime = filemtime($this->cacheFile);
            $time = time();
            $age = $time - $modifTime;
            if ($this->duration < $age)
                return;
            
            return $this->getContents();
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
