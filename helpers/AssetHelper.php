<?php
namespace tachyon\helpers;

/**
 * class AssetHelper
 * Работа с JS скриптами
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
class AssetHelper
{
    /** @const Путь к опубликованным скриптам */
    const ASSETS_PATH = __DIR__ . '/../../../public/assets';
    /** @const Путь к исходникам скриптов */
    const SOURCE_PATH = __DIR__ . '/../js';

    /**
     * Опубликованные скрипты
     * @var string $scripts
     */
    public static $scripts;
    
    public static function getCore($name)
    {
        if (!isset(self::$scripts[$name])) {
            return self::$scripts[$name] = self::publish($name);
        }
        return '';
    }

    public static function publish($name)
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
            copy(self::SOURCE_PATH . "/$name", $jsPath);

        return "<script type=\"text/javascript\" src=\"/assets/js/core/$name\"></script>";
    }
}