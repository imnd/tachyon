<?php
namespace tachyon\components;

/**
 * Компонент защиты от csrf-атак
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
class Csrf extends \tachyon\Component
{
    # сеттеры DIC
    use \tachyon\dic\Encrypt;

    public function __construct()
    {
        if (!isset($_SESSION))
            session_start();
    }

    /**
     * получение уникального id token`а
     * (извлечение из $_SESSION либо генерация случайного)
     * 
     * @return string
     */
    public function getTokenId()
    {
        if (!isset($_SESSION['token_id']))
            $_SESSION['token_id'] = 'csrf_' . $this->encrypt->randString(10);

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
        if (!isset($_SESSION['token_value']))
            $_SESSION['token_value'] = $this->encrypt->randString();

        return $_SESSION['token_value']; 
    }
    
    /**
     * проверка token`ов, передаваемых ч/з запросы
     * 
     * @return boolean
     */
    public function isTokenValid()
    {
        return $this->_isValid($_GET) || $this->_isValid($_POST);
    }

    /**
     * проверка token`а
     * 
     * @return boolean
     */
    private function _isValid($var)
    {
        return isset($var[$this->getTokenId()]) && $var[$this->getTokenId()]===$this->getTokenVal();
    }
}
