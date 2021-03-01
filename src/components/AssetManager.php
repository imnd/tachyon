<?php
namespace tachyon\components;

use tachyon\Config;

/**
 * Работа со скриптами и стилями
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2020 IMND
 */
class AssetManager
{
    /** @const Папка www */
    const PUBLIC_PATH = __DIR__ . '/../../../../public';
    /** @const Путь к исходникам скриптов */
    const CORE_JS_SOURCE_PATH = __DIR__ . '/../js';
    /** @const спец. символы javascript */
    const SPEC_SYMBOLS = '\?{}\(\)\[\],;:|=-';
    const TAGS = [
        'js'  => 'script',
        'css' => 'link',
    ];

    /**
     * @var Config $config
     */
    protected $config;

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

    /**
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @param string $name
     * @param string $publicPath
     * @param string | null $sourcePath
     *
     * @return string
     */
    public function css(string $name, string $publicPath = 'css', string $sourcePath = null): string
    {
        return $this->_publishFile($name, 'css', $publicPath, $sourcePath);
    }

    /**
     * @param string $name
     * @param string $publicPath
     * @param string | null $sourcePath
     *
     * @return string
     */
    public function js(string $name, string $publicPath = 'js', string $sourcePath = null): string
    {
        return $this->_publishFile($name, 'js', $publicPath, $sourcePath);
    }

    /**
     * @param string $name
     * @param string $ext
     * @param string $publicPath
     * @param string | null $sourcePath
     *
     * @return string
     */
    private function _publishFile(
        string $name,
        string $ext,
        string $publicPath,
        string $sourcePath = null
    ): string {
        $publicPath = "{$this->assetsPublicPath}/$publicPath";
        $filePath = "$publicPath/$name.$ext";
        if (!isset(self::$files[$filePath])) {
            if (!is_null($sourcePath) && !is_file($filePath)) {
                $text = $this->_readFile($name, $ext, $sourcePath);
                $this->_writeFile($name, $ext, $text, $publicPath);
            }
            $tag = self::TAGS[$ext];
            return self::$files[$filePath] = $this->$tag($filePath);
        }
        return '';
    }

    /**
     * @param string $name
     * @return void
     */
    public function coreJs(string $name): void
    {
        $this->_registerFile($name, 'js', self::CORE_JS_SOURCE_PATH);
    }

    /**
     * @param string $name
     * @param string $ext
     * @param string | null $sourcePath
     * @return void
     */
    private function _registerFile(string $name, string $ext, string $sourcePath = null): void
    {
        if (is_null($sourcePath)) {
            $sourcePath = self::PUBLIC_PATH . "/$ext";
        }
        if (!isset(self::$$ext[$name]) && !is_null($sourcePath)) {
            self::$$ext[$name] = $this->_readFile($name, $ext, $sourcePath);
        }
    }

    /**
     * @param string $name
     * @param string $ext
     * @param string $sourcePath
     *
     * @return string | string[] | null
     */
    private function _readFile(string $name, string $ext, string $sourcePath)
    {
        $text = file_get_contents("$sourcePath/$name.$ext");
        // удаляем лишние символы
        if ($this->config->get('env')==='production') {
            $text = $this->_clearify($text);
        }

        if ($ext==='js' && strpos($name, '.min')===false) {
//            $text = $this->_minimize($text, $name);
        }
        return $text;
    }

    /**
     * @param string $text
     *
     * @return string|string[]|null
     */
    private function _clearify(string $text)
    {
        $text = str_replace(['https://', 'http://'], '', $text);
        // многострочные комменты
        $text = preg_replace('!/\*.*?\*/!s', '', $text);
        // однострочные комменты
        $text = preg_replace('/\/{2,}.*\n/', '', $text);
        // все whitespaces
        $text = preg_replace('/[\n\t\r]/', ' ', $text);
        // лишние пробелы
        $text = trim(preg_replace('/[ ]{2,}/', ' ', $text));
        $text = preg_replace("/([" . self::SPEC_SYMBOLS . "])[ ]/", '$1', $text);
        $text = preg_replace("/[ ]([" . self::SPEC_SYMBOLS . "])/", '$1', $text);
        // лишние "," и ";"
        $text = preg_replace('/[,;](})/', '$1', $text);
        return $text;
    }

    /**
     * @param string $text
     *
     * @return string|string[]|null
     */
    private function _minimize(string $text)
    {
        $keywords = ['abstract', 'arguments', 'boolean', 'break', 'byte', 'case', 'catch', 'char', 'const', 'continue', 'debugger', 'default', 'delete', 'do', 'double', 'else', 'eval', 'false', 'final', 'finally', 'float', 'for', 'function', 'goto', 'if', 'implements', 'in', 'instanceof', 'int', 'interface', 'let', 'long', 'native', 'new', 'null', 'package', 'private', 'protected', 'public', 'return', 'short', 'static', 'switch', 'synchronized', 'this', 'throw', 'throws', 'transient', 'true', 'try', 'typeof', 'var', 'void', 'volatile', 'while', 'with', 'yield', 'class', 'enum', 'export', 'extends', 'import', 'super'];
        $varNames = array_merge(range('a', 'z'), range('A', 'Z'));
        $words = array_filter(preg_split('/[\s' . self::SPEC_SYMBOLS . ']/', $text));
        $i=0;
        foreach ($words as $word) {
            if (!in_array($word, $keywords)) {
                $text = preg_replace("/\\b$word\\b/", $varNames[$i++], $text);
            }
        }
        return $text;
    }

    /**
     * записываем
     *
     * @param string $name
     * @param string $ext
     * @param string $text
     * @param string $publicPath
     */
    private function _writeFile(
        string $name,
        string $ext,
        string $text,
        string $publicPath
    ) {
        $path = self::PUBLIC_PATH;
        $publicPathArr = explode('/', $publicPath);
        foreach ($publicPathArr as $subPath) {
            $path .= "/$subPath";
            if (!is_dir($path)) {
                if (!mkdir($path) && !is_dir($path)) {
                    throw new \RuntimeException(sprintf('Directory "%s" was not created', $path));
                }
            }
        }
        $fileName = "$path/$name.$ext";
        file_put_contents($fileName, $text);
        file_put_contents("$fileName.gz", gzencode($text, 9));
    }

    /**
     * @param string  $dirName
     * @param string | null $publicPath
     * @param string | null $sourcePath
     */
    public function publishFolder(
        string $dirName,
        string $publicPath = null,
        string $sourcePath = null
    ) {
        $sourcePath = $sourcePath ?? $this->assetsSourcePath;
        $publicPath = $publicPath ?? $this->assetsPublicPath;
        $sourcePath .= "/$dirName";
        $publicPath .= "/$dirName";
        $sourceDir = dir($sourcePath);
        while ($fileName = $sourceDir->read()) {
            if ($fileName{0} != '.') {
                $pathinfo = pathinfo($fileName);
                $name = $pathinfo['filename'];
                $ext = $pathinfo['extension'];
                $text = $this->_readFile($name, $ext, $sourcePath);
                $this->_writeFile($name, $ext, $text, $publicPath);
            }
        } 
        $sourceDir->close();
    }

    /**
     * @param string $contents
     */
    public function finalize(string &$contents)
    {
        $spritesTags = '';
        foreach (self::TAGS as $ext => $tag) {
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
            $filePath = "$publicPath/$spriteName.$ext";
            if (
                   $this->config->get('env')!=='production'
                || !is_file($filePath)
            ) {
                file_put_contents($filePath, $spriteText);
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
