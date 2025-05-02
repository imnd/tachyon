<?php

namespace tachyon\interfaces;

/**
 * @author imndsu@gmail.com
 */
interface HasOwnerInterface
{
    public function getOwner(): mixed;

    public function setOwner(mixed $owner = null): void;
}
