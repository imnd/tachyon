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
    # сеттеры DIC
    use \tachyon\dic\Lang;

    private $_messages = array();

    /**
     * Инициализация
     * 
     * @return void
     */
    public function __construct()
    {
        $this->loadMessages("{$this->get('config')->get('base_path')}/tachyon/config/lang/{$this->get('lang')->getLanguage()}.php");
        $this->loadMessages("{$this->get('config')->get('base_path')}/../app/config/lang/{$this->get('lang')->getLanguage()}.php");
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
