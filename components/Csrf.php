<?php

namespace tachyon\components;

use tachyon\components\Encrypt,
    tachyon\Config;

/**
 * Компонент защиты от CSRF-атак
 *
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
class Csrf
{
    /**
     * @var Config $config
     */
    protected $config;
    /**
     * @var Encrypt $encrypt
     */
    protected $encrypt;

    public function __construct(Config $config, Encrypt $encrypt)
    {
        $this->config = $config;
        $this->encrypt = $encrypt;
    }

    private $_started = false;

    private function start()
    {
        if (!isset($_SESSION) && !$this->_started) {
            session_start();
            $this->_started = true;
        }
        return $this;
    }

    /**
     * получение уникального id token`а
     * (извлечение из $_SESSION либо генерация случайного)
     *
     * @return string
     */
    public function getTokenId()
    {
        $this->start();
        if (!isset($_SESSION['token_id'])) {
            $_SESSION['token_id'] = 'csrf_' . $this->encrypt->randString(10);
        }
        return $_SESSION['token_id'];
    }

    /**
     * получение значения token`а
     * (извлечение из $_SESSION либо генерация случайного)
     *
     * @return string
     */
    public function getTokenVal()
    {
        $this->start();
        if (!isset($_SESSION['token_value'])) {
            $_SESSION['token_value'] = $this->encrypt->randString();
        }
        return $_SESSION['token_value'];
    }

    /**
     * проверка token`ов, передаваемых ч/з запросы
     *
     * @return boolean
     */
    public function isTokenValid()
    {
        return
            $this->config->get('csrf_check') !== true
            || $this->_isValid($_GET)
            || $this->_isValid($_POST);
    }

    /**
     * проверка token`а
     *
     * @return boolean
     */
    private function _isValid($var)
    {
        return isset($var[$this->getTokenId()]) && $var[$this->getTokenId()] === $this->getTokenVal();
    }
}
