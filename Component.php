<?php
namespace tachyon;

use \tachyon\dic\Container;

/**
 * Базовый класс для всех классов приложения.
 * Содержит несколько общих функций.
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
abstract class Component
{
    use \tachyon\dic\Config;

    /**
     * @var $properties array
     */
    protected $properties = array();
    /**
     * Объект, вызывающий сервис
     * @var mixed $domain
     */
    protected $domain;

    /**
     * @param mixed $domain
     * @return void
     */
    public function setDomain($domain = null)
    {
        $this->domain = $domain;
    }

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
        $params['domain'] = $this;
        $serviceName = ucfirst($serviceName);
        $varName = lcfirst($serviceName);
        if (property_exists($this, $varName)) {
            if (!is_null($this->$varName)) {
                // если уже есть одноименная скалярная переменная
                if (gettype($this->$varName)!='object') {
                    return Container::getInstanceOf($serviceName, $this, $params);
                }
            } else {
                $this->$varName = Container::getInstanceOf($serviceName, $this, $params);
            }
            return $this->$varName;
        }
        return Container::getInstanceOf($serviceName, $this, $params);
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
