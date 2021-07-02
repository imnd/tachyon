<?php
namespace tachyon;

use ErrorException;

/**
 * Класс инкапсулирующий конфигурацию
 *
 * @author Андрей Сердюк
 * @copyright (c) 2020 IMND
 */
class Config
{
    public const APP_DIR = '/../../../';

    /**
     * Все опции
     * @var array
     */
    private array $options;
    /**
     * Путь к папке файлу настроек
     * @var string
     */
    private string $filePath = self::APP_DIR . 'app/config/main.php';

    /**
     * @param string|null $mode
     */
    public function __construct(string $mode = null)
    {
        $basePath = dirname(str_replace('\\', '/', realpath(__DIR__)));
        // все опции
        $this->options = require("$basePath{$this->filePath}");
        // base path
        $this->options['base_path'] = $basePath;
        // environment
        $this->options['mode'] = defined('APP_MODE') ? APP_MODE : $mode ?? 'work';
        // read .env file
        $envFileName = ($this->options['mode']==='test') ? '.env-test' : '.env';
        if (!$envFile = file($basePath . self::APP_DIR . $envFileName)) {
            return;
        }
        foreach ($envFile as $string) {
            if ("\n"===$string || "\r\n"===$string) {
                continue;
            }
            $arr = explode(':', $string);
            $key = trim($arr[0]);
            if (0===strpos($key, '#')) {
                continue;
            }
            $val = trim($arr[1]);
            if (false!==$point = strpos($key, '.')) {
                $key0 = substr($key, 0, $point);
                if (!isset($this->options[$key0])) {
                    $this->options[$key0] = array();
                }
                $key1 = substr($key, $point+1);
                $this->options[$key0][$key1] = $val;
            } else {
                $this->options[$key] = $val;
            }
        }
    }

    /**
     * Извлечение значения по ключу
     *
     * @param string $key
     *
     * @return mixed
     */
    public function get(string $key)
    {
        return $this->options[$key] ?? null;
    }
}
