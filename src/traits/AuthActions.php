<?php
namespace tachyon\traits;

use tachyon\exceptions\HttpException,
    tachyon\Request;

/**
 * Трейт аутентификации
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2020 IMND
 */ 
trait AuthActions
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
    /**
     * Адрес страницы логина
     * @var string $loginUrl
     */
    private $loginUrl = '/login';

    /**
     * Перенаправляет пользователя на адрес логина
     * 
     * @return void
     */
    public function accessDenied()
    {
        Request::setReferer();
        $this->redirect($this->loginUrl);
    }

    /**
     * Юзер не авторизован
     * 
     * @return void
     */
    public function unauthorised($msg)
    {
        throw new HttpException($msg, HttpException::UNAUTHORIZED);
    }

    /**
     * Авторизован ли юзер
     * 
     * @return boolean
     */
    public function isAuthorised()
    {
        if (!$cookie = $this->cookie->get($this->cookieKey)) {
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
        $duration = $remember ? ($this->config->get('remember') ?: $this->remember) : 1;
        $this->cookie->setDuration($duration);
        $this->cookie->set($this->cookieKey, $this->_getCookieValue());
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
        $this->cookie->delete($this->cookieKey);
    }
}
