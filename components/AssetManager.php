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
    public static $scripts;
    
    public function js($name)
    {
        $name .= '.js';
        if (!isset(self::$scripts[$name])) {
            $this->copyFile($name, self::SOURCE_JS_PATH);
            return self::$scripts[$name] = "<script type=\"text/javascript\" src=\"/assets/js/core/$name\"></script>";
        }
        return '';
    }

    public function copyFile($name, $sourcePath)
    {
        $jsPath = self::ASSETS_PATH;
        if (!is_dir($jsPath)) {
            mkdir($jsPath);
        }
        $jsPath .= '/js';
        if (!is_dir($jsPath)) {
            mkdir($jsPath);
        }
        $jsPath .= '/core';
        if (!is_dir($jsPath)) {
            mkdir($jsPath);
        }
        $jsPath .= "/$name";
        if (!is_file($jsPath))
            copy("$sourcePath/$name", $jsPath);
    }
}