<?php

namespace tachyon\components;

use Iterator;

/**
 * Methods for creating and displaying flash messages
 *
 * @author imndsu@gmail.com
 */
class Flash
{
    /** @const Message types */
    public const FLASH_TYPE_ANY = 'any';
    public const FLASH_TYPE_ERROR = 'error';
    public const FLASH_TYPE_SUCCESS = 'success';
    public const FLASH_TYPES = [
        self::FLASH_TYPE_ANY,
        self::FLASH_TYPE_ERROR,
        self::FLASH_TYPE_SUCCESS,
    ];

    /**
     * Create message
     *
     * @param string $message
     * @param string $type
     * @return void
     */
    public function setFlash(string $message, string $type = self::FLASH_TYPE_ANY): void
    {
        $_SESSION["message_$type"] = $message;
    }

    /**
     * Add message
     *
     * @param string $message
     * @param string $type
     * @return void
     */
    public function addFlash(string $message, string $type = self::FLASH_TYPE_ANY): void
    {
        if (!isset($_SESSION)) {
            $_SESSION["message_$type"] = '';
        }
        $_SESSION["message_$type"] .= "\n$message";
    }

    /**
     * Retrieve
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
     * Retrieve all messages
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
