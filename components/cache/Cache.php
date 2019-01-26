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
    protected $duration = 60;
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
        if ($this->get('config')->getOption('mode')!=='production' || !isset($cache[$type])) {
            return;
        }
        $this->enabled = false;
        $options = $cache[$type];
        foreach ($options as $key => $value) {
            if (property_exists($type, $key)) {
                $this->$key = $value;
            }
        }
    }

    /**
     * возвращает содержимое кэша или включает буфферинг вывода
     * @param string $cacheKey
     */
    public function start($cacheKey)
    {
        if (!$this->enabled) {
            return;
        }
        if ($cacheContents = $this->getContents($cacheKey)) {
            return $cacheContents;
        }
        // запускаем кеширование
        $this->setKey($cacheKey);
        ob_start();
    }

    /**
     * заканчиваем кеширование
     * @param string $contents
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

    /**
     * слив содержимого вывода в хранилище
     * @param string $contents
     */
    abstract protected function save($contents);

    /**
     * возвращает содержимое кэша
     * @param string $cacheKey
     */
    abstract protected function getContents($key);

    protected function setKey($key)
    {
        $this->key = md5($key);
    }
}
