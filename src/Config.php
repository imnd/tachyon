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
    private $_options;
    private $_filePath = '../../app/config';
    private $_fileName = 'main';
    private $_env = 'debug';

    /**
     * @param string $fileName
     */
    public function __construct($env = null)
    {
        if (!is_null($env)) {
            $this->_env = $env;
        }
        $this->_fileName = "{$this->_filePath}/{$this->_fileName}.php";

        $basePath = dirname(str_replace('\\', '/', realpath(__DIR__)));
        // все опции
        $this->_options = require("$basePath/{$this->_fileName}");
        // base path
        $this->_options['base_path'] = $basePath;
        // read .env
        $envFile = ($this->_env==='test') ? '.env-test' : '.env';
        if (!$env = file("$basePath/../../$envFile")) {
            throw new \ErrorException("File $envFile not found");
        }
        foreach ($env as $string) {
            if ("\n"===$string) {
                continue;
            }
            $arr = explode(':', $string);
            $key = trim($arr[0]);
            if (1==strpos($key, '#')) {
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
                $this->_options[] = $val;
        }
    }

    /**
     * Извлечение опции
     * @param string $optionName
     * @return mixed
     */
    public function get($optionName)
    {
        return $this->_options[$optionName] ?? null;
    }
}
