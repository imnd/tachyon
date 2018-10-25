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
    const CORE_JS_SOURCE_PATH = __DIR__ . '/../js';

    /**
     * Путь к публичной папке со скриптами
     * @var string $assetsPath
     */
    private $assetsPublicPath = 'assets';
    /**
     * Путь к папке со скриптами
     * @var string $assetsSource
     */
    private $assetsSourcePath;

    /**
     * Опубликованные скрипты
     * @var string $scripts
     */
    public static $files = array();

    public function css($name, $publicPath = 'css', $sourcePath = null)
    {
        return $this->_publishFile('link', "$name.css", $publicPath, $sourcePath);
    }

    public function js($name, $publicPath = 'js', $sourcePath = null)
    {
        return $this->_publishFile('script', "$name.js", $publicPath, $sourcePath);
    }

    public function coreJs($name)
    {
        return $this->_publishFile('script', "$name.js", 'js/core', self::CORE_JS_SOURCE_PATH);
    }

    public function publishFolder($dirName, $publicPath = null, $sourcePath = null)
    {
        if (is_null($sourcePath))
            $sourcePath = $this->assetsSourcePath;

        if (is_null($publicPath))
            $publicPath = $this->assetsPublicPath;

        $sourcePath .= "/$dirName";
        $publicPath .= "/$dirName";
        $dir = dir($sourcePath);
        while ($fileName = $dir->read()) {
            if ($fileName{0} != '.') {
                $this->_copyFile($fileName, $sourcePath, $publicPath);
            };
        } 
        $dir->close();
    }

    private function _publishFile($tag, $name, $publicPath, $sourcePath = null)
    {
        $publicPath = "{$this->assetsPublicPath}/$publicPath";
        $filePath = "$publicPath/$name";
        if (!isset(self::$files[$filePath])) {
            if (!is_null($sourcePath) && !is_file($filePath)) {
                $this->_copyFile($name, $sourcePath, $publicPath);
            }
            return self::$files[$filePath] = $this->$tag($filePath);
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
        return "<link rel=\"stylesheet\" href=\"/$path\" type=\"text/css\" media=\"screen\">";
    }

    private function _copyFile($name, $sourcePath, $publicPath)
    {
        $publicPathArr = explode('/', $publicPath);
        $path = self::PUBLIC_PATH;
        foreach ($publicPathArr as $subPath) {
            $path .= "/$subPath";
            if (!is_dir($path)) {
                mkdir($path);
            }
        }
        copy("$sourcePath/$name", "$path/$name");
    }

    /**
     * @param string $path
     * @return void
     */
    public function setAssetsPublicPath($path)
    {
        $this->assetsPublicPath = $path;
    }

    /**
     * @param string $path
     * @return void
     */
    public function setAssetsSourcePath($path)
    {
        $this->assetsSourcePath = $path;
    }
}
