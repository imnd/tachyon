<?php
namespace tachyon\components;

/**
 * class Cookie
 * Инкапсулирует работу с cookie
 * TODO: сделать singleton'ом
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
class Cookie extends \tachyon\Component
{
    /**
     * Время жизни куки дней
     * @var integer $duration
     */
    protected $duration;

    public function getCookie($key)
    {
        if (isset($_COOKIE[$key])) {
            return $_COOKIE[$key];
        }
    }
    
    public function setCookie($key, $val, $path = '/')
    {
        setcookie($key, $val, time() + 30 * 24 * 60 * $this->duration, $path);
    }

    public function deleteCookie($key, $path = '/')
    {
        setcookie($key, null, -1, $path, $this->config->getOption('domain'));
    }

    /**
     * @param integer $val
     * @return void
     */
    public function setDuration($val)
    {
        $this->duration = $val;
    }
}
