<?php
namespace tachyon\components;

/**
 * class AssetManager
 * Работа с JS скриптами
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
class AssetManager
{
    /** @const Папка www */
    const PUBLIC_PATH = __DIR__ . '/../../../public';
    /** @const Путь к исходникам скриптов */
    const SOURCE_JS_PATH = __DIR__ . '/../js';

    /**
     * Опубликованные скрипты
     * @var string $scripts
     */
    public static $files = array();

    public function css($name, $target = array('assets', 'css'), $source = null)
    {
        return $this->_publish("$name.css", $target, $source, 'link');
    }

    public function js($name, $target = array('assets', 'js'), $source = null)
    {
        return $this->_publish("$name.js", $target, $source, 'script');
    }

    public function coreJs($name)
    {
        return $this->_publish("$name.js", array('assets', 'js', 'core'), self::SOURCE_JS_PATH, 'script');
    }

    private function _publish($name, $target, $source=null, $tag)
    {
        if (!is_array($target))
            $target = explode('/', $target);

        $targetPath = implode('/', $target) . "/$name";
        if (!isset(self::$files[$targetPath])) {
            if (!is_null($source))
                $this->_copyFile($name, $source, $target);

            return self::$files[$targetPath] = $this->$tag($targetPath);
        }
        return '';
    }

    /**
     * @param string $path путь
     * @return string
     */
    private function script($path)
    {
        return "<script type=\"text/javascript\" src=\"/$path\"></script>";
    }

    /**
     * @param string $path путь
     * @return string
     */
    private function link($path)
    {
        return "<link rel=\"stylesheet\" href=\"/$path\">";
    }

    private function _copyFile($name, $source, $pathArr)
    {
        if (is_file(implode('/', $pathArr) . "/$name"))
            return;

        $path = self::PUBLIC_PATH;
        foreach ($pathArr as $subPath) {
            $path .= "/$subPath";
            if (!is_dir($path))
                mkdir($path);
        }

        copy("$source/$name", "$path/$name");
    }
}