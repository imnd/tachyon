<?php
namespace tachyon\cache;

/**
 * Caching db content
 * 
 * @author imndsu@gmail.com
 */
class Db extends Cache
{
    protected bool $serialize = true;

    /** @inheritdoc */
    public function start(string $cacheKey): ?string
    {
        if (!$this->enabled) {
            return null;
        }
        if ($cacheContents = $this->getContents($cacheKey)) {
            return $cacheContents;
        }
        // запускаем кеширование
        $this->setKey($cacheKey);

        return null;
    }

    /** @inheritdoc */
    public function end(mixed $contents = null): void
    {
        if (!$this->enabled) {
            return;
        }
        $this->save($contents);
    }
}
