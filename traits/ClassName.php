<?php
namespace tachyon\traits;

/**
 * @author Андрей Сердюк
 * @copyright (c) 2019 IMND
 */ 
trait ClassName
{
    /**
     * get classname without namespace
     */
    public function getClassName()
    {
        return (new \ReflectionClass($this))->getShortName();
    }
}
