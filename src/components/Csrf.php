<?php
namespace tachyon\components;

use tachyon\Config;

/**
 * CSRF protection component
 *
 * @author imndsu@gmail.com
 */
class Csrf
{
    public function __construct(protected Config $config, protected Encrypt $encrypt)
    {
    }

    private bool $_started = false;

    private function start(): void
    {
        if (session_status() === PHP_SESSION_NONE && !$this->_started) {
            session_start();
            $this->_started = true;
        }
    }

    /**
     * Obtaining a unique token (extracting from $_SESSION or generating a random one)
     *
     * @return string
     */
    public function getTokenId(): string
    {
        $this->start();
        if (!isset($_SESSION['token_id'])) {
            $_SESSION['token_id'] = 'csrf_' . $this->encrypt->randString(10);
        }
        return $_SESSION['token_id'];
    }

    /**
     * Getting the token value (extracting from $_SESSION or generating a random one)
     *
     * @return string
     */
    public function getTokenVal(): string
    {
        $this->start();
        if (!isset($_SESSION['token_value'])) {
            $_SESSION['token_value'] = $this->encrypt->randString();
        }
        return $_SESSION['token_value'];
    }

    /**
     * Checking tokens transmitted through requests
     */
    public function isTokenValid(): bool
    {
        return
               $this->config->get('CSRF_CHECK') !== true
               // check token
            || isset($_POST[$this->getTokenId()])
               && hash_equals($this->getTokenVal(), $_POST[$this->getTokenId()]);
    }
}
