<?php
namespace tachyon\helpers;

use Iterator;

/**
 * Класс для создания и отображения флэш сообщений
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2019 IMND
 */
class FlashHelper
{
    /** @const Типы сообщений */
    const TYPE_ERROR = 'error';
    const TYPE_SUCCESS = 'success';
    const TYPES = [
        self::TYPE_ERROR,
        self::TYPE_SUCCESS,
    ];

    /**
     * Создание
     * 
     * @param string $message
     * @param string $type
     */
    public static function set($message, $type)
    {
        $_SESSION["message_$type"] = $message;
    }

    /**
     * Извлечение
     * 
     * @param string $type
     */
    public static function get($type)
    {
        if ($message = $_SESSION["message_$type"] ?? null) {
            unset($_SESSION["message_$type"]);
            return $message;
        }
    }

    /**
     * Извлечение всех сообщений
     * 
     * @return array
     */
    public static function getAll(): Iterator
    {
        foreach (self::TYPES as $type) {
            if ($message = self::get($type)) {
                yield $type => $message;
            }
        }
    }
}
