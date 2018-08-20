<?php
namespace tachyon\dic;

/**
 * Трэйт сеттера конфига
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
trait Encrypt
{
    /**
     * @var \tachyon\components\Encrypt $encrypt
     */
    protected $encrypt;

    /**
     * @param \tachyon\components\Encrypt $service
     * @return void
     */
    public function setEncrypt(\tachyon\components\Encrypt $service)
    {
        $this->encrypt = $service;
    }

    /**
     * @return \tachyon\components\Encrypt
     */
    public function getEncrypt()
    {
        if (is_null($this->encrypt)) {
            $this->encrypt = \tachyon\dic\Container::getInstanceOf('Encrypt');
        }
        return $this->encrypt;
    }
}
