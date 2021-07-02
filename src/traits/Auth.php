<?php
namespace tachyon\traits;

use ReflectionException;
use tachyon\exceptions\ContainerException;
use tachyon\exceptions\HttpException;

/**
 * Трейт аутентификации
 *
 * @author Андрей Сердюк
 * @copyright (c) 2020 IMND
 */
trait Auth
{
    /**
     * Имя переменной куки
     * @var string $cookieKey
     */
    private string $cookieKey = 'authorized';
    /**
     * Время жизни куки дней при нажатой кнопке "remember me"
     * @var integer $remember
     */
    private int $remember = 7;
    /**
     * Адрес страницы логина
     * @var string $loginUrl
     */
    private string $loginUrl = '/login';

    /**
     * Перенаправляет пользователя на адрес логина
     *
     * @return bool
     */
    public function accessDenied(): bool
    {
        $this->request->setReferer();
        $this->redirect($this->loginUrl);
        return false;
    }

    /**
     * Юзер не авторизован
     *
     * @param $msg
     *
     * @return void
     * @throws HttpException
     */
    public function unauthorised($msg): void
    {
        throw new HttpException($msg, HttpException::UNAUTHORIZED);
    }

    /**
     * Авторизован ли юзер
     *
     * @return boolean
     */
    public function isAuthorised(): bool
    {
        if (!$cookie = $this->cookie->get($this->cookieKey)) {
            return false;
        }
        return $cookie===$this->_getCookieValue();
    }

    /**
     * Авторизован ли юзер
     *
     * @return bool
     */
    public function checkAccess(): bool
    {
        if ($this->isAuthorised()) {
            return true;
        }
        return $this->accessDenied();
    }

    /**
     * залогинить юзера
     *
     * @param bool $remember
     *
     * @return void
     * @throws ReflectionException | ContainerException
     */
    protected function _login($remember = false): void
    {
        $duration = $remember ? (config('remember') ?: $this->remember) : 1;
        $this->cookie->setDuration($duration);
        $this->cookie->set($this->cookieKey, $this->_getCookieValue());
    }

    /**
     * Защита от воровства куки авторизации
     * берется набор уникальных данных о пользователе (айпи, порт, строка юзер-агента браузера) и хэшируется
     *
     * @return string
     */
    private function _getCookieValue(): string
    {
        return md5($_SERVER['REMOTE_ADDR'] . $_SERVER['SERVER_PORT'] . $_SERVER['HTTP_USER_AGENT']);
    }

    /**
     * Разлогинить юзера
     * @return void
     */
    protected function _logout(): void
    {
        $this->cookie->delete($this->cookieKey);
    }
}
