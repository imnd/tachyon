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

    /**
     * @return \tachyon\components\Lang
     */
    public function getLang()
    {
        if (is_null($this->lang)) {
            $this->lang = \tachyon\dic\Container::getInstanceOf('Lang');
        }
        return $this->lang;
    }
}
