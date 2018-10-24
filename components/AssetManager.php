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

    public function css($name, $source, $target = array('assets', 'css'))
    {
        return $this->_publish("$name.css", $source, $target, 'link');
    }

    public function js($name, $source, $target = array('assets', 'js'))
    {
        return $this->_publish("$name.js", $source, $target, 'script');
    }

    public function coreJs($name)
    {
        return $this->_publish("$name.js", self::SOURCE_JS_PATH, array('assets', 'js', 'core'), 'script');
    }

    private function _publish($name, $source, $target, $tag)
    {
        $targetPath = implode('/', $target) . "/$name";
        if (!isset(self::$files[$targetPath])) {
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