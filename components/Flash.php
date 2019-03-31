<?php
namespace tachyon\components;

use Iterator;

/**
 * Методы для создания и отображения флэш сообщений
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2019 IMND
 */
class Flash
{
    /** @const Типы сообщений */
    const FLASH_TYPE_ANY = 'any';
    const FLASH_TYPE_ERROR = 'error';
    const FLASH_TYPE_SUCCESS = 'success';
    const FLASH_TYPES = [
        self::FLASH_TYPE_ANY,
        self::FLASH_TYPE_ERROR,
        self::FLASH_TYPE_SUCCESS,
    ];

    /**
     * Создание
     * 
     * @param string $message
     * @param string $type
     */
    public function setFlash($message, $type = self::FLASH_TYPE_ANY)
    {
        $_SESSION["message_$type"] = $message;
    }

    /**
     * Создание
     * 
     * @param string $message
     * @param string $type
     */
    public function addFlash($message, $type = self::FLASH_TYPE_ANY)
    {
        if (!isset($_SESSION)) {
            $_SESSION["message_$type"] = '';
        }
        $_SESSION["message_$type"] .= "\n$message";
    }

    /**
     * Извлечение
     * 
     * @param string $type
     */
    public function getFlash($type = self::FLASH_TYPE_ANY)
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
    public function getAllFlashes(): Iterator
    {
        foreach (self::FLASH_TYPES as $type) {
            if ($message = $this->getFlash($type)) {
                yield $type => $message;
            }
        }
    }
}
