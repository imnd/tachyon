<?php
namespace tachyon\components;

/**
 * class Lang
 * Класс работы с языковыми настройками
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
class Lang extends \tachyon\Component
{
    # сеттеры DIC
    use \tachyon\dic\Cookie;

    private $_lang;

    /**
     * Инициализация
     * @return void
     */
    public function __construct()
    {
        $cookieService = $this->get('cookie');
        // установка и текущего языка из cookie
        if (!$lang = $cookieService->getCookie('lang')) {
            $lang = $this->get('config')->get('lang');
            $cookieService->setCookie('lang', $lang);
        }
        $this->_lang = $lang;
    }

    /**
     * извлечение текущего языка
     */
    public function getLanguage()
    {
        return $this->_lang;
    }
}
