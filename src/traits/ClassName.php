<?php
namespace tachyon\traits;

/**
 * @author Андрей Сердюк
 * @copyright (c) 2020 IMND
 */ 
trait ClassName
{
    /**
     * get classname without namespace
     */
    public function getClassName($className = '')
    {
        if (''!==$className) {
            return substr($className, strrpos($className, '\\') + 1);;
        }
        return (new \ReflectionClass($this))->getShortName();
    }
}
