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
    /** @const Путь к опубликованным скриптам */
    const ASSETS_PATH = __DIR__ . '/../../../public/assets';
    /** @const Путь к исходникам скриптов */
    const SOURCE_JS_PATH = __DIR__ . '/../js';

    /**
     * Опубликованные скрипты
     * @var string $scripts
     */
    public static $files;
    
    public function js($name)
    {
        $name .= '.js';
        if (!isset(self::$files[$name])) {
            $this->copyFile($name, self::SOURCE_JS_PATH);
            return self::$files[$name] = "<script type=\"text/javascript\" src=\"/assets/js/core/$name\"></script>";
        }
        return '';
    }

    public function copyFile($name, $sourcePath)
    {
        $path = self::ASSETS_PATH;
        if (is_file("$path/js/core/$name"))
            return;

        if (!is_dir($path)) {
            mkdir($path);
        }
        $path .= '/js';
        if (!is_dir($path)) {
            mkdir($path);
        }
        $path .= '/core';
        if (!is_dir($path)) {
            mkdir($path);
        }
        $path .= "/$name";
        if (!is_file($path))
            copy("$sourcePath/$name", $path);
    }
}