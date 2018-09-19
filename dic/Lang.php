<?php
namespace tachyon\dic;

/**
 * Трэйт сеттера языковых настроек
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
trait Lang
{
    /**
     * @var \tachyon\components\Lang $lang
     */
    protected $lang;

    /**
     * @param \tachyon\components\Lang $service
     * @return void
     */
    public function setLang(\tachyon\components\Lang $service)
    {
        $this->lang = $service;
    }
}
