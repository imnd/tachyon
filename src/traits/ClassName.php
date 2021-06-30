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
     * @return false | string
     */
    /**
     * get classname without namespace
     *
     * @param string $className
     *
     * @return false | string
     */
    public function getClassName(string $className = '')
    {
        if ('' !== $className) {
            return substr($className, strrpos($className, '\\') + 1);
        }
        return (new ReflectionClass($this))->getShortName();
    }

    /**
     * Convert dashes to camel case
     *
     * @param string $className
     *
     * @return string
     */
    public function kebabToCamel(string $className): string
    {
        return str_replace(' ', '', ucwords(str_replace('-', ' ', $className)));
    }
}
