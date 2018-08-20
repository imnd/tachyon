<?php
namespace tachyon\dic;

/**
 * Трэйт сеттера конфига
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
trait Csrf
{
    /**
     * @var \tachyon\components\Csrf $csrf
     */
    protected $csrf;

    /**
     * @param \tachyon\components\Csrf $service
     * @return void
     */
    public function setCsrf(\tachyon\components\Csrf $service)
    {
        $this->csrf = $service;
    }

    /**
     * @return \tachyon\components\Csrf
     */
    public function getCsrf()
    {
        if (is_null($this->csrf)) {
            $this->csrf = \tachyon\dic\Container::getInstanceOf('Csrf');
        }
        return $this->csrf;
    }
}
