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
}
