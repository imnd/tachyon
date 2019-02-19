<?php
namespace tachyon\components;

/**
 * class Message
 * Класс работы с текстовыми сообщениями
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
class Message extends \tachyon\Component
{
    private $_messages = array();

    /**
     * Инициализация
     * 
     * @return void
     */
    public function __construct()
    {
        $basePath = $this->get('config')->get('base_path');
        $lang = $this->get('lang')->getLanguage();
        $this->loadMessages("$basePath/tachyon/lang/$lang.php");
        $this->loadMessages("$basePath/../app/config/lang/$lang.php");
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
