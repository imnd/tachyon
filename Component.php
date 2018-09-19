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
     * @param string $serviceName
     * @param array $params динамически назначаемые параметры
     * @return mixed
     */
    public function getService($serviceName, array $params = array())
    {
        $serviceName = ucfirst($serviceName);
        $varName = lcfirst($serviceName);
        if (property_exists($this, $varName)) {
            if (!is_null($this->$varName)) {
                // если уже есть одноименная скалярная переменная
                if (gettype($this->$varName)!='object')
                    return \tachyon\dic\Container::getInstanceOf($serviceName, $params);
            } else {
                $this->$varName = \tachyon\dic\Container::getInstanceOf($serviceName, $params);
            }
            return $this->$varName;
        }
        return \tachyon\dic\Container::getInstanceOf($serviceName, $params);
    }

    /**
     * Извлекает сервис из контейнера по его id
     * шорткат
     * 
     * @param string $serviceName
     * @param array $params динамически назначаемые параметры
     * @return mixed
     */
    public function get($serviceName, array $params = array())
    {
        return $this->getService($serviceName, $params);
    }

    /**
     * get classname without namespace
     */
    public function getClassName()
    {
        return (new \ReflectionClass($this))->getShortName();
    }
}
