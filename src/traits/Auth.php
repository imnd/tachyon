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
     * bind to client IP and User-Agent, and sign with a server-side secret key.
     */
    private function _getCookieValue(): string
    {
        $secret = config('cookie_secret') ?: 'sdnv5ln0vlz8nbl4emr';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        return hash_hmac('sha256', $ip . $userAgent, $secret);
    }

    protected function _logout(): void
    {
        $this->cookie->delete($this->cookieKey);
    }
}
