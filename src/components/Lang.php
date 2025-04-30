<?php
namespace tachyon\components;

use tachyon\Config;

/**
 * class for working with the language settings
 *
 * @author imndsu@gmail.com
 */
class Lang
{
    protected Config $config;
    protected Cookie $cookie;

    public function __construct(Config $config, Cookie $cookie)
    {
        $this->config = $config;
        $this->cookie = $cookie;
    }

    /**
     * retrieving current language
     */
    public function getLanguage(): string
    {
        // setting the current language from cookies
        if (!$lang = $this->cookie->get('lang')) {
            $lang = $this->config->get('lang');
            $this->cookie->set('lang', $lang);
        }
        return $lang;
    }
}
