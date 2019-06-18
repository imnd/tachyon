<?php
namespace tachyon\components;

/**
 * Работа со скриптами и стилями
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2019 IMND
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
     * Массив скриптов для склеивания и публикации
     * @var array $js
     */
    private static $js = array();
    /**
     * Массив стилей для склеивания и публикации
     * @var array $css
     */
    private static $css = array();
    /**
     * Опубликованные скрипты
     * @var string $scripts
     */
    private static $files = array();

    public function css($name, $publicPath = 'css', $sourcePath = null)
    {
        return $this->_publishFile('link', "$name.css", $publicPath, $sourcePath);
    }

    public function js($name, $publicPath = 'js', $sourcePath = null)
    {
        return $this->_publishFile('script', "$name.js", $publicPath, $sourcePath);
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

    public function coreJs($name)
    {
        return $this->_registerFile($name, 'js', self::CORE_JS_SOURCE_PATH);
    }

    private function _registerFile($name, $ext, $sourcePath = null)
    {
        if (is_null($sourcePath)) {
            $sourcePath = self::PUBLIC_PATH . "/$ext";
        }
        if (!isset(self::$$ext[$name]) && !is_null($sourcePath)) {
            $text = $this->_minimize(file_get_contents("$sourcePath/$name.$ext"));
            self::$$ext[$name] = $text;
        }
    }

    public function finalize(&$contents)
    {
        $spritesTags = '';
        foreach ([
            'js' => 'script',
            'css' => 'link',
        ] as $ext => $tag) {
            $publicPath = "{$this->assetsPublicPath}/$ext";
            $spriteText = $spriteName = '';
            foreach (self::$$ext as $name => $text) {
                $spriteName .= $name;
                $spriteText .= "$text ";
            }
            if (empty($spriteText)) {
                continue;
            }
            $spriteName = md5($spriteName);
            if (!is_dir($publicPath)) {
                mkdir($publicPath);
            }
            if (!is_file($filePath = "$publicPath/$spriteName.$ext")) {
                file_put_contents($filePath, $text);
                file_put_contents("$filePath.gz", gzencode($spriteText, 9));
            }
            $spritesTags .= "{$this->$tag($filePath)} ";
        }
        if (empty($spritesTags)) {
            return;
        }
        $contents = str_replace('</head>', "$spritesTags</head>", $contents);
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
        $text = $this->_minimize(file_get_contents("$sourcePath/$name"));
        // записываем
        $fileName = "$path/$name";
        file_put_contents($fileName, $text);
        file_put_contents("$fileName.gz", gzencode($text, 9));
    }

    private function _minimize($text)
    {
        // многострочные комменты
        $text = preg_replace('!/\*.*?\*/!s', '', $text);
        // однострочные комменты
        $text = preg_replace('/\/{2,}.*\n/', '', $text);
        // все whitespaces
        $text = preg_replace('/[\n\t]/', ' ', $text);
        // лишние пробелы
        $text = trim(preg_replace('/[ ]{2,}/', ' ', $text));
        $specSymb = '[\?{}\(\)\[\],;:|=-]';
        $text = preg_replace("/($specSymb)[ ]/", '$1', $text);
        $text = preg_replace("/[ ]($specSymb)/", '$1', $text);
        // лишние , и ;
        $text = preg_replace('/[,;](})/', '$1', $text);
        return $text;
    }

    public function publishFolder($dirName, $publicPath = null, $sourcePath = null)
    {
        $sourcePath = $sourcePath ?? $this->assetsSourcePath;
        $publicPath = $publicPath ?? $this->assetsPublicPath;
        $sourcePath .= "/$dirName";
        $publicPath .= "/$dirName";
        $sourceDir = dir($sourcePath);
        while ($fileName = $sourceDir->read()) {
            if ($fileName{0} != '.') {
                $this->_copyFile($fileName, $sourcePath, $publicPath);
            }
        } 
        $sourceDir->close();
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
