<?php

namespace tachyon\helpers;

use ReflectionClass;
use ReflectionException;

/**
 * @author imndsu@gmail.com
 */
class ClassHelper
{
    /**
     * Get class name without namespace
     * @throws ReflectionException
     */
    public static function getClassName($arg): string
    {
        if (is_string($arg)) {
            return substr($arg, strrpos($arg, '\\') + 1);
        }
        return (new ReflectionClass($arg))->getShortName();
    }
}
