<?php
namespace tachyon;

class Env
{
    private string $env;

    public function __construct(Config $config)
    {
        $this->env = $config->get('env');
    }

    public function isProduction(): bool
    {
        return $this->env === 'production';
    }

    public function isDevelop(): bool
    {
        return $this->env === 'develop';
    }

    public function isLocal(): bool
    {
        return $this->env === 'local';
    }
}
