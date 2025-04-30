<?php

namespace tachyon\components;

use tachyon\Env;
use RuntimeException;

/**
 * Работа со скриптами и стилями
 *
 * @author imndsu@gmail.com
 */
class AssetManager
{
    /** @const Папка www */
    const PUBLIC_FOLDER_PATH = __DIR__ . '/../../../../../public';
    /** @const Путь к исходникам скриптов */
    const TAGS = [
        'js' => 'script',
        'css' => 'link',
    ];

    protected Env $env;

    /**
     * Путь к публичной папке со скриптами
     */
    private string $assetsPublicPath = 'assets';

    /**
     * Путь к папке со скриптами
     */
    private string $assetsSourcePath;

    /**
     * Массив скриптов для склеивания и публикации
     */
    private static $js = [];

    private static $varIndex = 0;

    /**
     * Массив стилей для склеивания и публикации
     */
    private static $css = [];

    /**
     * Опубликованные скрипты
     */
    private static $files = [];

    /**
     * core скрипты опубликованы
     */
    private static $finalized = false;

    public function __construct(Env $env)
    {
        $this->env = $env;
        if (!is_dir($this->assetsPublicPath)) {
            mkdir($this->assetsPublicPath);
        }
    }

    public function css(string $name, string $publicPath = 'css', string $sourcePath = null): string
    {
        return $this->publishFile($name, 'css', $publicPath, $sourcePath);
    }

    public function js(string $name, string $publicPath = 'js', string $sourcePath = null): string
    {
        return $this->publishFile($name, 'js', $publicPath, $sourcePath);
    }

    public function moduleJs(string $name, string $publicPath = 'js', string $sourcePath = null): string
    {
        return $this->publishFile($name, 'js', $publicPath, $sourcePath, [
            'type' => 'module',
        ]);
    }

    private function publishFile(
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
                $text = $this->getFileContents($name, $ext, $sourcePath);
                $this->writeFile($name, $ext, $text, $publicPath);
            }
            $tag = self::TAGS[$ext];

            return self::$files[$filePath] = $this->$tag($filePath, $options);
        }
        return '';
    }

    private function getFileContents(string $name, string $ext, string $sourcePath)
    {
        $text = file_get_contents("$sourcePath/$name.$ext");

        if ($this->env->isProduction()) {
            $this->clearify($text);
        }
        return $text;
    }

    /**
     * удаляем лишние символы
     */
    private function clearify(string &$text): void
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

    private function replaceVar(&$text, $varName): void
    {
        $text = preg_replace("/([^a-zA-Z0-9])($varName)([^a-zA-Z0-9])/", '$1v' . self::$varIndex++ . '$3', $text);
    }

    private function writeFile(
        string $name,
        string $ext,
        string $text,
        string $publicPath
    ): void {
        $path = self::PUBLIC_FOLDER_PATH;
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

    public function publishFolder(
        string $dirName,
        string $publicPath = null,
        string $sourcePath = null
    ): void {
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
                $text = $this->getFileContents($name, $ext, $sourcePath);
                $this->writeFile($name, $ext, $text, $publicPath);
            }
        }
        $sourceDir->close();
    }

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
     */
    public function publishSeparated(): void
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
     */
    private function publishSprite(&$contents): void
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

    private function link($path)
    {
        return "<link rel=\"stylesheet\" href=\"/$path\" type=\"text/css\" media=\"screen\">";
    }

    public function setAssetsPublicPath($path)
    {
        $this->assetsPublicPath = $path;
    }

    public function setAssetsSourcePath($path)
    {
        $this->assetsSourcePath = $path;
    }
}
