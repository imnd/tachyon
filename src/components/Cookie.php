<?php
namespace tachyon\components;

use tachyon\Config;

/**
 * Инкапсулирует работу с cookie
 *
 * @author imndsu@gmail.com
 */
class Cookie
{
    protected Config $config;

    /**
     * Время жизни куки дней
     */
    private int $duration = 1;
    /**
     * Защищенные куки могут быть переданы только через шифрованное соединение
     */
    private bool $secure = false;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function get($key)
    {
        return $_COOKIE[$key] ?? null;
    }

    public function set(string $key, $val, string $path = '/'): void
    {
        setcookie($key, htmlentities($val), time() + 30 * 24 * 60 * $this->duration, $path, $this->config->get('domain') ?? '', $this->secure, true);
    }

    public function delete(string $key, string $path = '/'): void
    {
        setcookie($key, null, -1, $path);
    }

    public function setDuration(int $val): static
    {
        $this->duration = $val;
        return $this;
    }

    public function setSecure(bool $val): static
    {
        $this->secure = $val;
        return $this;
    }
}
