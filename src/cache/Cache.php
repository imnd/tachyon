<?php
namespace tachyon\cache;

use ReflectionClass,
    ReflectionException,
    tachyon\Config;

/**
 * кеширование
 *
 * @author Андрей Сердюк
 * @copyright (c) 2020 IMND
 */
abstract class Cache
{
    protected $duration = 60;
    protected $cacheFolder = '../runtime/cache/';
    protected $cacheFile = '';
    protected $key = '';
    protected $enabled = false;
    protected $serialize = false;

    /**
     * Инициализация
     *
     * @param Config $config
     *
     * @throws ReflectionException
     */
    public function __construct(Config $config)
    {
        $type = strtolower((new ReflectionClass($this))->getShortName());
        $cache = $config->get('cache');
        if ($config->get('env')!=='production' || !isset($cache[$type])) {
            return;
        }
        $options = $cache[$type];
        foreach ($options as $key => $value) {
            if (property_exists($type, $key)) {
                $this->$key = $value;
            }
        }
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

    /**
     * @param $key
     *
     * @return false|mixed|string|void
     */
    protected function getContents($key)
    {
        $this->setKey($key);
        $this->setCacheFilePath();
        if (file_exists($this->cacheFile)) {
            $modifiedAt = filemtime($this->cacheFile);
            $time = time();
            $age = $time - $modifiedAt;
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
    }

    protected function setKey($key): void
    {
        $this->key = md5($key);
    }

    /**
     * @inheritdoc
     */
    protected function save($contents): void
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
