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
    private $_options = array();
	private $_fileName = '../app/config/main.php';

    public function __construct()
    {
        $basePath = dirname(str_replace('\\', '/', realpath(__DIR__)));
		// все опции
		$this->_options = require("$basePath/{$this->_fileName}");
		// base path
		$this->_options['base_path'] = $basePath;
	}

    /**
     * извлечение опции
     */
    public function getOption($optionName)
    {
        if (isset($this->_options[$optionName]))
            return $this->_options[$optionName];
        if (isset($this->_options['site_vars'][$optionName]))
            return $this->_options['site_vars'][$optionName];
    }
}
