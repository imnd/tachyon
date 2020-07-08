<?php
namespace tachyon\traits;

/**
 * @author Андрей Сердюк
 * @copyright (c) 2020 IMND
 */ 
trait HasOwner
{
    /**
     * Объект, вызывающий сервис
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
     * @return void
     */
    public function setOwner($owner = null)
    {
        $this->owner = $owner;
    }
}
