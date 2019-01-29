<?php
namespace tachyon\components;

use tachyon\helpers\ArrayHelper;

/**
 * Инкапсулирует работу с cookie
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
    private $duration;

    public function getCookie($key)
    {
        return $_COOKIE[$key] ?? null;
    }
    
    public function setCookie($key, $val, $path = '/')
    {
        setcookie($key, ArrayHelper::filterText($val), time() + 30 * 24 * 60 * $this->duration, $path);
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
