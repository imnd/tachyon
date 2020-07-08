<?php
namespace tachyon\traits;

/**
 * @author Андрей Сердюк
 * @copyright (c) 2020 IMND
 */ 
trait Configurable
{
    /**
     * Установка переменных объекта
     * @param array $params
     * @return void
     */
    public function setVariables(array $params = array())
    {
        foreach ($params as $param => $value)
            $this->$param = $value;
    }
}
