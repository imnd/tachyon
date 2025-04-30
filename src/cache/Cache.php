<?php
namespace tachyon\cache;

use
    /*Psr\SimpleCache\CacheInterface,*/
    ReflectionClass,
    ReflectionException,
    tachyon\Config,
    tachyon\Env;

/**
 * @author imndsu@gmail.com
 */
abstract class Cache /*implements CacheInterface*/
{
    protected Env $env;

    protected int $duration = 60;
    protected string $cacheFolder = '../runtime/cache';
    protected string $cacheFile = '';
    protected string $key = '';
    protected bool $enabled = false;
    protected bool $serialize = false;

    /**
     * @throws ReflectionException
     */
    public function __construct(Env $env, Config $config)
    {
        $this->env = $env;
        $type = strtolower((new ReflectionClass($this))->getShortName());
        $cacheConf = $config->get('cache');
        if ($this->env->isProduction() || !isset($cacheConf[$type])) {
            return;
        }
        $options = $cacheConf[$type];
        foreach ($options as $key => $value) {
            if (property_exists($type, $key)) {
                $this->$key = $value;
            }
        }
    }

    /**
     * returns the contents of the file cache or includes output buffering
     */
    abstract public function start(string $cacheKey): ?string;

    /**
     * finishes caching (dump the contents of the output to a file)
     */
    abstract public function end(string $contents = null): void;

    /**
     * @return false|mixed|string|void
     */
    protected function getContents(string $key)
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

    protected function setKey(string $key): void
    {
        $this->key = md5($key);
    }

    protected function save($contents): void
    {
        $this->setCacheFilePath();
        if ($this->serialize) {
            $contents = serialize($contents);
        }
        file_put_contents($this->cacheFile, $contents);
    }

    protected function setCacheFilePath(): void
    {
        $this->cacheFile = "{$this->cacheFolder}/{$this->key}.php";
    }
}
