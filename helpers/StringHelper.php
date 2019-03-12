<?php
namespace tachyon\helpers;

/**
 * Содержит полезные функции для работы с текстом
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
class StringHelper
{
    /**
     * Encodes special characters into HTML entities.
     * The encoding - app charset will be used for encoding.
     * @param string $text data to be encoded
     * @return string the encoded data
     */
    public static function specChars($text)
    {
        return htmlspecialchars($text, ENT_QUOTES, \tachyon\dic\Container::getInstanceOf('Config')->getOption('encoding'));
    }

    /**
     * @param string $className
     * @return string
     */
    public static function getShortClassName($className)
    {
        if (!$a = strrchr($className, '\\')) {
            return $className;
        }
        return substr($a, 1);
    }
}
