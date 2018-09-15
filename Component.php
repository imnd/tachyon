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
     * @var $properties array
     */
    protected $properties = array();

    public function setProperty($var, $val)
    {
        $this->properties[$var] = $val;
    }

    public function getProperty($var)
    {
        if (isset($this->properties[$var])) {
            return $this->properties[$var];
        }
    }

    /**
     * Извлекает сервис из контейнера по его id
     * 
     * @param string $service
     * @return mixed
     */
    public function get($service)
    {
        return \tachyon\dic\Container::getInstanceOf($service, $this);
    }

    /**
     * get classname without namespace
     */
    public function getClassName()
    {
        return (new \ReflectionClass($this))->getShortName();
    }
}
