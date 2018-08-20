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
    public static function getNumEnding($number, $endingArray = array('', 'a', 'ов'))
    {
        $number = $number % 100;
        if ($number >= 11 && $number <= 19)
            $ending = $endingArray[2];
        else {
            $i = $number % 10;
            switch ($i) {
                case (1):
                    $ending = $endingArray[0];
                    break;
                case (2):
                case (3):
                case (4):
                    $ending = $endingArray[1];
                    break;
                default:
                    $ending = $endingArray[2];
            }
        }
        return $ending;
    }

    /**
     * Encodes special characters into HTML entities.
     * The encoding - app charset will be used for encoding.
     * @param string $text data to be encoded
     * @return string the encoded data
     */
    public static function specChars($text)
    {
        return htmlspecialchars($text, ENT_QUOTES, \tachyon\dic\Container::getInstanceOf('config')->getOption('encoding'));
    }

    /**
     * @param string $className
     * @return string
     */
    public static function getShortClassName($className)
    {
        if (!$a = strrchr($className, '\\'))
            return $className;
        
        return substr($a, 1);
    }
}
