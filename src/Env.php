<?php
namespace tachyon;

class Env
{
    private Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function isProduction(): bool
    {
        return $this->config->get('env') === 'production';
    }

    public function isDevelop(): bool
    {
        return $this->config->get('env') === 'develop';
    }

    public function isLocal(): bool
    {
        return $this->config->get('env') === 'local';
    }
}
