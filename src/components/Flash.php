<?php

namespace tachyon\components;

use Iterator;

/**
 * Методы для создания и отображения флэш сообщений
 *
 * @author Андрей Сердюк
 * @copyright (c) 2020 IMND
 */
class Flash
{
    /** @const Типы сообщений */
    public const FLASH_TYPE_ANY = 'any';
    public const FLASH_TYPE_ERROR = 'error';
    public const FLASH_TYPE_SUCCESS = 'success';
    public const FLASH_TYPES = [
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
    public function setFlash(string $message, $type = self::FLASH_TYPE_ANY): void
    {
        $_SESSION["message_$type"] = $message;
    }

    /**
     * Создание
     *
     * @param string $message
     * @param string $type
     */
    public function addFlash(string $message, $type = self::FLASH_TYPE_ANY): void
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
     *
     * @return mixed
     */
    public function getFlash(string $type = self::FLASH_TYPE_ANY)
    {
        if ($message = $_SESSION["message_$type"] ?? null) {
            unset($_SESSION["message_$type"]);
            return $message;
        }
    }

    /**
     * Извлечение всех сообщений
     *
     * @return Iterator
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
