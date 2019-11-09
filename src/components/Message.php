<?php
namespace tachyon\components;

use tachyon\Config,
    tachyon\components\Lang;

/**
 * class Message
 * Класс работы с текстовыми сообщениями
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
class Message
{
    private $_messages = array();

    /**
     * Инициализация
     * 
     * @return void
     */
    public function __construct(Config $config, Lang $lang)
    {
        $basePath = $config->get('base_path');
        $lng = $lang->getLanguage();
        $this->loadMessages("$basePath/src/lang/$lng.php");
        $this->loadMessages("$basePath/../../app/config/lang/$lng.php");
    }

    /**
     * @param string $path
     * @return void
     */
    private function loadMessages($path)
    {
        if (is_file($path)) {
            $this->_messages = array_merge($this->_messages, require($path));
        }
    }

    /**
     * Перевод текстового сообщения
     * 
     * @param string $msg
     * @param array $vars
     * @return string
     */
    public function i18n($msg, $vars = array())
    {
        if (!isset($this->_messages[$msg])) {
            return $msg;
        }
        $message = $this->_messages[$msg];
        if (!empty($vars)) {
            foreach ($vars as $key => $val) {
                $message = str_replace("%$key", $val, $message);
            }
        }
        return $message;
    }
}
