<?php
namespace tachyon;

/**
 * Базовый класс для всех классов приложения.
 * Содержит несколько общих функций.
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
abstract class Component
{
    /**
     * магическое присвоение значений переменным которых нет
     * @var $properties array
     */
    protected $properties = array();

    public function setProperty($var, $val)
    {
        $this->properties[$var] = $val;
    }

    public function getProperty($var)
    {
        if (isset($this->properties[$var]))
            return $this->properties[$var];
    }

    /**
     * get classname without namespace
     */
    public function getClassName()
    {
        return (new \ReflectionClass($this))->getShortName();
    }
}
