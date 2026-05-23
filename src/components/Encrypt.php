<?php

namespace tachyon\components;

/**
 * Содержит функции для работы с шифрованием
 *
 * @author imndsu@gmail.com
 */
class Encrypt
{
    /**
     * алгоритм для шифровки пароля
     *
     * @var string $algorithm
     */
    protected string $algorithm = 'md5';
    /**
     * соль для шифровки пароля (сохранено для обратной совместимости, если используется где-то)
     *
     * @var string $salt
     */
    protected string $salt = '';

    /**
     * @param $password
     *
     * @return string
     */
    public function hashPassword($password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * @param string $password
     * @param string $hash
     *
     * @return bool
     */
    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * @param int|null $len
     *
     * @return string
     */
    public function randString($len = null): string
    {
        $byteLen = is_null($len) ? 32 : (int)ceil($len / 2);
        try {
            $string = bin2hex(random_bytes($byteLen));
        } catch (\Exception $e) {
            $string = hash('sha256', microtime() . uniqid(mt_rand(), true));
        }
        if (!is_null($len)) {
            $string = substr($string, 0, $len);
        }
        return $string;
    }

    /**
     * @param string $val
     *
     * @return void
     */
    public function setAlgorithm($val): void
    {
        $this->algorithm = $val;
    }

    /**
     * @param string $val
     *
     * @return void
     */
    public function setSalt($val): void
    {
        $this->salt = (string)$val;
    }
}
