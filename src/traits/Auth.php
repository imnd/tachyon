<?php

namespace tachyon\traits;

use tachyon\exceptions\HttpException;

/**
 * @author imndsu@gmail.com
 */
trait Auth
{
    private string $cookieKey = 'authorized';
    /**
     * the lifetime of cookies days when the button "remember me" is pressed
     */
    private int $remember = 7;
    /**
     * login page address
     */
    private string $loginUrl = '/login';

    /**
     * redirects user to login
     */
    public function accessDenied(): void
    {
        $this->request->setReferer();
        $this->redirect($this->loginUrl);
    }

    /**
     * user not authorized
     *
     * @throws HttpException
     */
    public function unauthorised($msg): void
    {
        throw new HttpException($msg, HttpException::UNAUTHORIZED);
    }

    public function isAuthorised(): bool
    {
        if (!$cookie = $this->cookie->get($this->cookieKey)) {
            return false;
        }
        return $cookie === $this->_getCookieValue();
    }

    public function checkAccess(): ?bool
    {
        if ($this->isAuthorised()) {
            return true;
        }
        $this->accessDenied();
    }

    protected function _login(bool $remember = false): void
    {
        $duration = $remember ? (config('remember') ?: $this->remember) : 1;
        $this->cookie->setDuration($duration);
        $this->cookie->set($this->cookieKey, $this->_getCookieValue());
    }

    /**
     * theft protection of authorization cookies
     * take a set of unique data about the user (ip, port, user-agent of the browser) and hash it
     */
    private function _getCookieValue(): string
    {
        return md5($_SERVER['REMOTE_ADDR'] . $_SERVER['SERVER_PORT'] . $_SERVER['HTTP_USER_AGENT']);
    }

    protected function _logout(): void
    {
        $this->cookie->delete($this->cookieKey);
    }
}
