<?php
namespace tachyon;

class Env
{
    /**
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @return boolean
     */
    public function isProduction()
    {
        return $this->config->get('env') === 'production';
    }

    /**
     * @return boolean
     */
    public function isDevelop()
    {
        return $this->config->get('env') === 'develop';
    }

    /**
     * @return boolean
     */
    public function isLocal()
    {
        return $this->config->get('env') === 'local';
    }
}