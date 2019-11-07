<?php
namespace tachyon\components;

/**
 * Содержит функции для работы с шифрованием
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
class Encrypt
{
    /**
     * алгоритм для шифровки пароля
     * @var integer $salt
     */
    protected $algorithm = 'md5';
    /**
     * соль для шифровки пароля
     * @var integer $salt
     */
    protected $salt;
    
    public function hashPassword($password)
    {
        return hash($this->algorithm, $password . $this->salt);
    }

    public function randString($len=null)
    {
        $string = hash($this->algorithm, microtime());
        if (!is_null($len))
            $string = substr($string, 0, $len);
        
        return $string;
    }

    /**
     * @param string $val
     * @return void
     */
    public function setAlgorithm($val)
    {
        $this->algorithm = $val;
    }

    /**
     * @param string $val
     * @return void
     */
    public function setSalt($val)
    {
        $this->salt = $val;
    }
}
