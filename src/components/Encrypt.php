<?php

namespace tachyon\components;

/**
 * Contains functions for working with encryption
 *
 * @author imndsu@gmail.com
 */
class Encrypt
{
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
        $string = bin2hex(random_bytes($byteLen));
        if (!is_null($len)) {
            $string = substr($string, 0, $len);
        }
        return $string;
    }
}
