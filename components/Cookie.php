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
    use \tachyon\dic\Config;

    /**
     * Время жизни куки
     * @var integer $duration
     */
    protected $duration;

    public function getCookie($key)
    {
        if (isset($_COOKIE[$key])) {
            return $_COOKIE[$key];
        }
    }
    
    public function setCookie($key, $val, $secure=false, $httpOnly = false)
    {
        setcookie($key, $val, time() + 86400 * $this->duration, $this->config->getOption('base_path'), $this->config->getOption('domain'), $secure, $httpOnly);
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
