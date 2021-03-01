<?php
namespace tachyon\components;

use tachyon\Config;

/**
 * Класс работы с языковыми настройками
 *
 * @author Андрей Сердюк
 * @copyright (c) 2020 IMND
 */
class Lang
{
    /**
     * @var Config $config
     */
    protected Config $config;
    /**
     * @var Cookie $cookie
     */
    protected Cookie $cookie;

    /**
     * @param Config $config
     * @param Cookie $cookie
     */
    public function __construct(Config $config, Cookie $cookie)
    {
        $this->config = $config;
        $this->cookie = $cookie;
    }

    /**
     * извлечение текущего языка
     *
     * @return string
     */
    public function getLanguage(): string
    {
        // установка и текущего языка из cookie
        if (!$lang = $this->cookie->get('lang')) {
            $lang = $this->config->get('lang');
            $this->cookie->set('lang', $lang);
        }
        return $lang;
    }
}
