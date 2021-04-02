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
    /**
     * Все опции
     * @var array
     */
    private array $_options;
    /**
     * Путь к папке файлу настроек
     * @var string
     */
    private string $_filePath = '../../app/config/main.php';

    /**
     * @param string $mode
     *
     * @throws ErrorException
     */
    public function __construct(string $mode = null)
    {
        $basePath = dirname(str_replace('\\', '/', realpath(__DIR__)));
        // все опции
        $this->_options = require("$basePath/{$this->_filePath}");
        // base path
        $this->_options['base_path'] = $basePath;
        // environment
        $this->_options['mode'] = defined('APP_MODE') ? APP_MODE : $mode ?? 'work';
        // read .env file
        $envFileName = ($this->_options['mode']==='test') ? '.env-test' : '.env';
        if (!$envFile = file("$basePath/../../$envFileName")) {
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
                if (!isset($this->_options[$key0])) {
                    $this->_options[$key0] = array();
                }
                $key1 = substr($key, $point+1);
                $this->_options[$key0][$key1] = $val;
            } else {
                $this->_options[$key] = $val;
            }
        }
    }

    /**
     * Извлечение значения по ключу
     * @param string $optionName
     *
     * @return mixed
     */
    public function get($optionName)
    {
        return $this->_options[$optionName] ?? null;
    }
}
