<?php
namespace tachyon\components;

use tachyon\Config;

/**
 * Инкапсулирует работу с cookie
 *
 * @author Андрей Сердюк
 * @copyright (c) 2020 IMND
 */
class Cookie
{
    /**
     * @var Config $config
     */
    protected Config $config;

    /**
     * Время жизни куки дней
     * @var integer $duration
     */
    private int $duration = 1;
    /**
     * Защищенные куки могут быть переданы только через шифрованное соединение
     * @var boolean $secure
     */
    private bool $secure = false;

    /**
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function get($key)
    {
        return $_COOKIE[$key] ?? null;
    }

    public function set($key, $val, $path = '/'): void
    {
        setcookie($key, htmlentities($val), time() + 30 * 24 * 60 * $this->duration, $path, $this->config->get('domain') ?? '', $this->secure, true);
    }

    public function delete($key, $path = '/'): void
    {
        setcookie($key, null, -1, $path);
    }

    /**
     * @param integer $val
     * @return void
     */
    public function setDuration($val): void
    {
        $this->duration = $val;
    }

    /**
     * @param boolean $val
     * @return void
     */
    public function setSecure(bool $val): void
    {
        $this->secure = $val;
    }
}
