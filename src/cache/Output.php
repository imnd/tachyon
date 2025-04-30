<?php
namespace tachyon\cache;

/**
 * caching the contents of the entire page
 * 
 * @author imndsu@gmail.com
 */
class Output extends Cache
{
    /**
     * @inheritdoc
     */
    public function start(string $cacheKey): ?string
    {
        if (!$this->enabled) {
            return null;
        }
        if ($cacheContents = $this->getContents($cacheKey)) {
            echo $cacheContents;
            die;
        }
        // запускаем кеширование
        $this->setKey($cacheKey);
        ob_start();

        return null;
    }

    /**
     * @inheritdoc
     */
    public function end(string $contents = null): void
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
