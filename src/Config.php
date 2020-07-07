<?php
namespace tachyon;

/**
 * Класс инкапсулирующий конфигурацию
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
class Config
{
    /**
     * Все опции
     * @var array
     */
    private $_options;
    /**
     * Путь к папке файлу настроек
     * @var string
     */
    private $_filePath = '../../app/config/main.php';

    /**
     * @param string $fileName
     */
    public function __construct($env = null)
    {
        $basePath = dirname(str_replace('\\', '/', realpath(__DIR__)));
        // все опции
        $this->_options = require("$basePath/{$this->_filePath}");
        // base path
        $this->_options['base_path'] = $basePath;
        // environment
        $this->_options['env'] = defined('APP_ENV') ? APP_ENV : $env ?? 'debug';
        // read .env file
        $envFile = ($this->_options['env']==='test') ? '.env-test' : '.env';
        if (!$env = file("$basePath/../../$envFile")) {
            throw new \ErrorException("File $envFile not found");
        }
        foreach ($env as $string) {
            if ("\n"===$string) {
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
            } else
                $this->_options[$key] = $val;
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