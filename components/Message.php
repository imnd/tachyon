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

    private $_messages;

    /**
     * Инициализация
     * @return void
     */
    public function __construct()
    {
        // текстовые опции
        $this->_messages = require("{$this->get('config')->getOption('base_path')}/../app/config/lang/{$this->get('lang')->getLanguage()}.php");
    }

    /**
     * извлечение текстовых сообщений
     */
    public function i18n($message)
    {
        if (isset($this->_messages[$message]))
            return $this->_messages[$message];
    }
}
