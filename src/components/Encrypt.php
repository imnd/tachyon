<?php

namespace tachyon\components;

/**
 * Содержит функции для работы с шифрованием
 *
 * @author Андрей Сердюк
 * @copyright (c) 2020 IMND
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
     * соль для шифровки пароля
     *
     * @var integer $salt
     */
    protected int $salt;

    /**
     * @param $password
     *
     * @return string
     */
    public function hashPassword($password): string
    {
        return hash($this->algorithm, $password . $this->salt);
    }

    /**
     * @param null $len
     *
     * @return false|string
     */
    public function randString($len = null)
    {
        $string = hash($this->algorithm, microtime());
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
        $this->salt = $val;
    }
}
