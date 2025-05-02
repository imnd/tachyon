<?php
namespace tachyon\traits;

/**
 * @author imndsu@gmail.com
 */
trait HasOptions
{
    protected array $options = [];

    public function setOption(string $key, mixed $val): static
    {
        $this->options[$key] = $val;
        
        return $this;
    }

    public function getOption(string $key): mixed
    {
        return $this->options[$key] ?? null;
    }
}
