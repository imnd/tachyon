<?php

namespace tachyon\components;

use tachyon\Config;

/**
 * class Message
 * Класс работы с текстовыми сообщениями
 *
 * @author imndsu@gmail.com
 */
class Message
{
    private array $_messages = [];

    /**
     * @param Config $config
     * @param Lang   $lang
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
     *
     * @return void
     */
    private function loadMessages(string $path): void
    {
        if (is_file($path)) {
            $this->_messages = array_merge($this->_messages, require($path));
        }
    }

    /**
     * Перевод текстового сообщения
     *
     * @param string $msg
     * @param array  $vars
     *
     * @return string
     */
    public function t(string $msg, array $vars = []): string
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
