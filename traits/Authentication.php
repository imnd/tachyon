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
    private $cookieKey = 'authorized';
    /**
     * Время жизни куки дней при нажатой кнопке "remember me"
     * @var integer $remember
     */
    private $remember = 7;
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
     * Авторизован ли юзер
     * 
     * @return boolean
     */
    public function isAuthorised()
    {
        if (!$cookie = $this->cookie->getCookie($this->cookieKey)) {
            return false;
        }
        return $cookie===$this->_getCookieValue();
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
        $duration = 1;
        if ($remember) {
            $duration = $this->config->get('remember') ?: $this->remember;
        }
        $this->cookie->setDuration($duration);
        $this->cookie->setCookie($this->cookieKey, $this->_getCookieValue());
    }

    /**
     * Защита от воровства куки авторизации
     * берется набор уникальных данных о пользователе (айпи, порт, строка юзер-агента браузера) и хэшируется
     * 
     * @return string
     */
    private function _getCookieValue()
    {
        return md5($_SERVER['REMOTE_ADDR'] . $_SERVER['SERVER_PORT'] . $_SERVER['HTTP_USER_AGENT']);
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
