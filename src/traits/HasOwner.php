<?php

namespace tachyon\traits;

/**
 * @author imndsu@gmail.com
 */
trait HasOwner
{
    /**
     * Объект, вызывающий сервис
     *
     * @var mixed $owner
     */
    protected $owner;

    /**
     * @return mixed
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @param mixed $owner
     *
     * @return void
     */
    public function setOwner($owner = null): void
    {
        $this->owner = $owner;
    }
}
