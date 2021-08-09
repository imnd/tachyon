<?php

namespace tachyon\components;

use tachyon\Env;
use RuntimeException;

/**
 * Работа со скриптами и стилями
 *
 * @author Андрей Сердюк
 * @copyright (c) 2020 IMND
 */
class AssetManager
{
    /** @const Папка www */
    const PUBLIC_PATH = __DIR__ . '/../../../../../public';
    /** @const Путь к исходникам скриптов */
    const CORE_JS_SOURCE_PATH = __DIR__ . '/../js';
    const TAGS = [
        'js' => 'script',
        'css' => 'link',
    ];
    const CORE_SCRIPTS = [
        'ajax',
        'datepicker',
        'dom',
        'obj',
        'upload',
    ];

    /**
     * @var Env $env
     */
    protected $env;

    /**
     * Путь к публичной папке со скриптами
     *
     * @var string $assetsPath
     */
    private $assetsPublicPath = 'assets';

    /**
     * Путь к папке со скриптами
     *
     * @var string $assetsSource
     */
    private $assetsSourcePath;

    /**
     * Массив скриптов для склеивания и публикации
     *
     * @var array $js
     */
    private static $js = [];

    private static $varIndex = 0;

    /**
     * Массив стилей для склеивания и публикации
     *
     * @var array $css
     */
    private static $css = [];

    /**
     * Опубликованные скрипты
     *
     * @var string $scripts
     */
    private static $files = [];

    /**
     * core скрипты опубликованы
     */
    private static $finalized = false;

    /**
     * @param Env $env
     */
    public function __construct(Env $env)
    {
        $this->env = $env;
    }

    /**
     * @param string        $name
     * @param string        $publicPath
     * @param string | null $sourcePath
     *
     * @return string
     */
    public function css(string $name, string $publicPath = 'css', string $sourcePath = null): string
    {
        return $this->_publishFile($name, 'css', $publicPath, $sourcePath);
    }

    /**
     * @param string        $name
     * @param string        $publicPath
     * @param string | null $sourcePath
     *
     * @return string
     */
    public function js(string $name, string $publicPath = 'js', string $sourcePath = null): string
    {
        return $this->_publishFile($name, 'js', $publicPath, $sourcePath);
    }

    /**
     * @param string        $name
     * @param string        $publicPath
     * @param string | null $sourcePath
     *
     * @return string
     */
    public function moduleJs(string $name, string $publicPath = 'js', string $sourcePath = null): string
    {
        return $this->_publishFile($name, 'js', $publicPath, $sourcePath, [
            'type' => 'module',
        ]);
    }

    /**
     * @param string        $name
     * @param string        $ext
     * @param string        $publicPath
     * @param string | null $sourcePath
     *
     * @return string
     */
    private function _publishFile(
        string $name,
        string $ext,
        string $publicPath,
        string $sourcePath = null,
        array $options = []
    ): string {
        $publicPath = "{$this->assetsPublicPath}/$publicPath";
        $filePath = "$publicPath/$name.$ext";
        if (!isset(self::$files[$filePath])) {
            if (!is_null($sourcePath) && !is_file($filePath)) {
                $text = $this->_getFileContents($name, $ext, $sourcePath);
                $this->_writeFile($name, $ext, $text, $publicPath);
            }
            $tag = self::TAGS[$ext];

            return self::$files[$filePath] = $this->$tag($filePath, $options);
        }
        return '';
    }

    /**
     * @param string|null $names
     *
     * @return
     */
    public function coreJs(string $names = null): self
    {
        if (is_null($names)) {
            $names = self::CORE_SCRIPTS;
        } else {
            $names = [$names];
        }
        foreach ($names as $name) {
            if (!isset(self::$js[$name])) {
                self::$js[$name] = $this->_getFileContents($name, 'js', self::CORE_JS_SOURCE_PATH);
            }
        }
        return $this;
    }

    /**
     * @param string $name
     * @param string $ext
     * @param string $sourcePath
     *
     * @return string | string[] | null
     */
    private function _getFileContents(string $name, string $ext, string $sourcePath)
    {
        $text = file_get_contents("$sourcePath/$name.$ext");

        if ($this->env->isProduction()) {
            $this->_clearify($text);
//            $this->_obfuscate($text);
        }
        return $text;
    }

    /**
     * удаляем лишние символы
     *
     * @param string $text
     *
     * @return string|string[]|null
     */
    private function _clearify(string &$text)
    {
        $text = str_replace(['https://', 'http://'], '', $text);
        // вырезать слэши на концах строк в строковых переменных
        $text = str_replace("\\\n", '', $text);
        // вырезать многострочные комменты
        $text = preg_replace('!/\*.*?\*/!s', '', $text);
        // вырезать однострочные комменты
        $text = preg_replace('/\/{2,}.*\n/', '', $text);
        // все whitespaces заменить на пробелы
        $text = str_replace(["\n", "\t", "\r"], ' ', $text);
        // вырезать лишние пробелы
        $text = trim(preg_replace('/[ ]{2,}/', ' ', $text));
        $text = preg_replace('/([\(\)\{\}=+-,;:|])( )/', '${1}', $text);
        $text = preg_replace('/( )([()\{\}=+-,;:|])/', '${2}', $text);
        // лишние "," и ";"
        $text = preg_replace('/[,;](})/', '$1', $text);
    }

    /**
     * переименовать переменные
     *
     * @param string $text
     *
     * @return string
     */
    private function _obfuscate(string &$text)
    {
        // объявленные переменные
        preg_match_all('/(var|let|const)[ ]([^;]+)/', $text, $matches);
        foreach ($matches[2] as $i => $varNameGroup) {
            $varExpressions = explode(',', $varNameGroup);
            if ($i === 0) {
                $varExpression = $varExpressions[0];
                // первая переменная, название модуля.
                $moduleVarName = substr($varExpression, 0, strpos($varExpression, '='));
                continue;
            }
            foreach ($varExpressions as $varName) {
                if ($equalSignPos = strpos($varName, '=')) {
                    $varName = substr($varName, 0, $equalSignPos);
                }
                preg_match('/[^a-zA-Z0-9]/', $varName, $nonAlpha);
                if (!empty($nonAlpha)) {
                    continue;
                }
                if (is_numeric($varName)) {
                    continue;
                }
                if ($moduleVarName===$varName) {
                    continue;
                }
                $this->_replaceVar($text, $varName);
            }
        }
        // параметры ф-й
        preg_match_all('/[(]([a-zA-Z_]{1}[^)(.="\/ ]*)[)]/', $text, $matches);
        foreach ($matches[1] as $i => $varNameGroup) {
            $varNames = explode(',', $varNameGroup);
            foreach ($varNames as $varName) {
                if ($moduleVarName===$varName) {
                    continue;
                }
                $this->_replaceVar($text, $varName);
            }
        }
    }

    /**
     * @param string $text
     * @param string $varName
     * @return void
     */
    private function _replaceVar(&$text, $varName)
    {
        $text = preg_replace("/([^a-zA-Z0-9])($varName)([^a-zA-Z0-9])/", '$1v' . self::$varIndex++ . '$3', $text);
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
            if (!mkdir($path) && !is_dir($path)) {
                throw new RuntimeException(sprintf('Directory "%s" can not be created', $path));
            }
        }
        $fileName = "$path/$name.$ext";
        file_put_contents($fileName, $text);
        file_put_contents("$fileName.gz", gzencode($text, 9));
    }

    /**
     * @param string        $dirName
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
            if ($fileName[0] != '.') {
                $pathinfo = pathinfo($fileName);
                $name = $pathinfo['filename'];
                $ext = $pathinfo['extension'];
                $text = $this->_getFileContents($name, $ext, $sourcePath);
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
        if (self::$finalized) {
            return;
        }

        $this->publishSeparated();

        self::$finalized = true;
    }

    /**
     * публикация скриптов по отдельности
     *
     * @param string $contents
     * @return void
     */
    public function publishSeparated()
    {
        foreach (self::TAGS as $ext => $tag) {
            $scriptPath = "{$this->assetsPublicPath}/$ext";
            if (!is_dir($scriptPath)) {
                mkdir($scriptPath);
            }
            foreach (self::$$ext as $scriptName => $scriptText) {
                $filePath = "$scriptPath/$scriptName.$ext";
                if (
                       !$this->env->isProduction()
                    || !is_file($filePath)
                ) {
                    file_put_contents($filePath, $scriptText);
                    file_put_contents("$filePath.gz", gzencode($scriptText, 9));
                }
            }
        }
    }

    /**
     * склеивание скриптов в спрайт
     *
     * @param string $contents
     * @return void
     */
    private function publishSprite(&$contents)
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
                   !$this->env->isProduction()
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
     * @param array $options
     *
     * @return string
     */
    private function script(string $path, array $options = [])
    {
        if (!isset($options['type'])) {
            $options['type'] = "text/javascript";
        }
        $options['src'] = $path;

        $text = "";
        foreach ($options as $name => $option) {
            $text .= "$name='$option'";
        }

        return "<script $text></script>";
    }

    /**
     * @param string $path путь
     *
     * @return string
     */
    private function link($path)
    {
        return "<link rel=\"stylesheet\" href=\"/$path\" type=\"text/css\" media=\"screen\">";
    }

    /**
     * @param string $path
     *
     * @return void
     */
    public function setAssetsPublicPath($path)
    {
        $this->assetsPublicPath = $path;
    }

    /**
     * @param string $path
     *
     * @return void
     */
    public function setAssetsSourcePath($path)
    {
        $this->assetsSourcePath = $path;
    }
}
