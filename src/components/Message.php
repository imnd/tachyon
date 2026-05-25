<?php

namespace tachyon\components;

use tachyon\Config;

/**
 * Class for working with text messages
 *
 * @author imndsu@gmail.com
 */
class Message
{
    private array $_messages = [];

    public function __construct(Config $config, Lang $lang)
    {
        $basePath = $config->get('base_path');
        $lng = $lang->getLanguage();
        $this->loadMessages("$basePath/src/lang/$lng.php");
        $this->loadMessages(APP_ROOT . "/app/config/lang/$lng.php");
    }

    private function loadMessages(string $path): void
    {
        if (is_file($path)) {
            $this->_messages = array_merge($this->_messages, require($path));
        }
    }

    /**
     * Text message translation
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
