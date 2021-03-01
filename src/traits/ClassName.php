<?php

namespace tachyon\traits;

use ReflectionClass;
use ReflectionException;

/**
 * @author Андрей Сердюк
 * @copyright (c) 2020 IMND
 */
trait ClassName
{
    /**
     * get classname without namespace
     *
     * @param string $className
     *
     * @return false|string
     * @throws ReflectionException
     */
    public function getClassName($className = '')
    {
        if ('' !== $className) {
            return substr($className, strrpos($className, '\\') + 1);
        }
        return (new ReflectionClass($this))->getShortName();
    }
}
