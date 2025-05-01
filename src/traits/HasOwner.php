<?php

namespace tachyon\traits;

/**
 * @author imndsu@gmail.com
 */
trait HasOwner
{
    protected mixed $owner;

    public function getOwner(): mixed
    {
        return $this->owner;
    }

    public function setOwner(mixed $owner = null): void
    {
        $this->owner = $owner;
    }
}
