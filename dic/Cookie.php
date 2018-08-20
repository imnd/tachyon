<?php
namespace tachyon\dic;

/**
 * Трэйт сеттера куки
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
trait Cookie
{
    protected $cookie;

    /**
     * @param \tachyon\components\Cookie $service
     * @return void
     */
    public function setCookie(\tachyon\components\Cookie $service)
    {
        $this->cookie = $service;
    }

    /**
     * @return \tachyon\components\Cookie
     */
    public function getCookie()
    {
        if (is_null($this->cookie)) {
            $this->cookie = \tachyon\dic\Container::getInstanceOf('Cookie');
        }
        return $this->cookie;
    }
}
