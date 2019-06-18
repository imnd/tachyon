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
    // перенести в services.php
    private $_fileName = '../app/config/main.php';

    /**
     * Извлечение опции
     * @param string $optionName
     * @return mixed
     */
    public function get($optionName)
    {
        if (is_null($this->_options)) {
            $basePath = dirname(str_replace('\\', '/', realpath(__DIR__)));
            // все опции
            $this->_options = require("$basePath/{$this->_fileName}");
            // base path
            $this->_options['base_path'] = $basePath;
        }

        return $this->_options[$optionName] ?? $this->_options['site_vars'][$optionName] ?? null;
    }
}
