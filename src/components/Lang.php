<?php
namespace tachyon\components;

use tachyon\Config,
    tachyon\components\Cookie;

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
    protected $config;
    /**
     * @var Cookie $cookie
     */
    protected $cookie;

    /**
     * @return void
     */
    public function __construct(Config $config, Cookie $cookie)
    {
        $this->config = $config;
        $this->cookie = $cookie;
    }

    /**
     * извлечение текущего языка
     */
    public function getLanguage()
    {
        // установка и текущего языка из cookie
        if (!$lang = $this->cookie->get('lang')) {
            $lang = $this->config->get('lang');
            $this->cookie->set('lang', $lang);
        }
        return $lang;
    }
}
