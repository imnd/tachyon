<?php
namespace tachyon\dic;

/**
 * Трэйт сеттера конфига
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
trait Config
{
    /**
     * @var \tachyon\Config $config
     */
    protected $config;

    /**
     * @param \tachyon\Config $service
     * @return void
     */
    public function setConfig(\tachyon\Config $service)
    {
        $this->config = $service;
    }

    /**
     * @return \tachyon\Config
     */
    public function getConfig()
    {
        if (is_null($this->config)) {
            $this->config = \tachyon\dic\Container::getInstanceOf('Config');
        }
        return $this->config;
    }
}
