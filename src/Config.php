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

    /**
     * @param string $fileName
     */
    public function __construct($fileName = null)
    {
        if (!is_null($fileName)) {
            $this->_fileName = $fileName;
        }
        $this->_fileName = "{$this->_filePath}/{$this->_fileName}.php";

        $basePath = dirname(str_replace('\\', '/', realpath(__DIR__)));
        // все опции
        $this->_options = require("$basePath/{$this->_fileName}");
        // base path
        $this->_options['base_path'] = $basePath;
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
