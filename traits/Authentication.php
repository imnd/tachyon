<?php
namespace tachyon\traits;

/**
 * Трейт аутентификации
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */ 
trait Authentication
{
    /**
     * Имя переменной куки
     * @var string $cookieKey
     */
    private $cookieKey = 'userid';
    /**
     * Время жизни куки дней
     * @var integer $duration
     */
    private $duration = 1;
    private $loginUrl = '/index/login';

    /**
     * Перенаправляет пользователя на адрес логина
     * 
     * @return void
     */
    public function accessDenied()
    {
        $this->setReferer();
        $this->redirect($this->loginUrl);
    }

    /**
     * ID авторизованного юзера
     * 
     * @return integer
     */
    public function getUserId()
    {
        return $this->cookie->getCookie($this->cookieKey);
    }

    /**
     * Авторизован ли юзер
     * 
     * @return boolean
     */
    public function isAuthorised()
    {
        return !empty($this->getUserId());
    }

    /**
     * Авторизован ли юзер
     * 
     * @return boolean
     */
    public function checkAccess()
    {
        if (!$this->isAuthorised()) {
            $this->accessDenied();
        }
    }

    /**
     * залогинить юзера
     * @return void
     */
    protected function _login($remember=false)
    {
        $duration = $this->duration;
        if ($remember) {
            $duration *= 7;
        }
        $this->cookie->setDuration($duration);
        $this->cookie->setCookie($this->cookieKey, 1);
    }

    /**
     * Разлогинить юзера
     * @return void
     */
    protected function _logout()
    {
        $this->cookie->deleteCookie($this->cookieKey);
    }
}
