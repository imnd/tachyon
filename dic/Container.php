<?php
namespace tachyon\dic;

use ReflectionClass,
    tachyon\exceptions\NotFoundException,
    tachyon\exceptions\ContainerException
;

/**
 * Dependency Injection Container
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
class Container implements ContainerInterface
{
    /**
     * Массив инстанциированных компонентов
     * @var $_services array
     */
    private $_services = array();
    /**
     * Конфигурация компонентов и их параметров
     * @var $_config array
     */
    private $_config = array();
    /**
     * Сопоставление интерфейсов и их реализаций
     * @var $_implementations array
     */
    private $_implementations;

    public function __construct()
    {
        $this->_loadConfig();
    }

    /**
     * Загружаем компоненты и параметры компонентов в массив $_config
     */
    private function _loadConfig()
    {
        $basePath = dirname(str_replace('\\', '/', realpath(__DIR__)));
        $coreConfText = file_get_contents("$basePath/dic/services.json");
        $elements = json_decode($coreConfText, true);
        if (
                file_exists($appConfPath = "$basePath/../../app/config/services.json")
            and $appConfText = file_get_contents($appConfPath)
            and $appElements = json_decode($appConfText, true)
        ) {
            $elements = array_merge($elements, $appElements);
        }
        foreach ($elements as $element) {
            $class = $element['class'];
            if (isset($this->_config[$class])) {
                continue;
            }
            $service = [
                'variables' => array(),
                'singleton' => !empty($element['singleton']),
            ];
            if (isset($element['properties'])) {
                foreach ($element['properties'] as $property) {
                    $propName = $property['name'];
                    if (!empty($property['value'])) {
                        $service['variables'][$propName] = $property['value'];
                    }
                }
            }
            $this->_config[$class] = $service;
        }
        $implementationFile = "$basePath/../../app/config/implementations.php";
        if (file_exists($implementationFile)) {
            $this->_implementations = require($implementationFile);
        }
    }

    /**
     * Создает экземпляр сервиса
     * 
     * @param string $name
     * @param mixed $owner объект "хозяин" сервиса
     * @param array $params динамически назначаемые параметры
     * @return mixed
     */
    public function get($className, array $params = array())
    {
        if (
                $config = $this->_getVariables($className)
            and !empty($config['singleton'])
        ) {
            if (!isset($this->_services[$className])) {
                $this->_services[$className] = $this->resolve($className, $params);
            }
            return $this->_services[$className];
        }
        return $this->resolve($className, $params);
    }

    public function has($id)
    {
        return isset($this->_services[$name]);
    }

    /**
     * Извлечение реализации интерфейса
     */
    public function getImplementation($interface)
    {
        return $this->_implementations[$interface] ?? null;
    }

    /**
     * Создает экземпляр сервиса
     * 
     * @param array $config
     * @param array $params
     * @return void
     * @throws ContainerException
     */
    private function resolve(string $name, array $params = array())
    {
        $reflection = new ReflectionClass($name);
        if ($reflection->isInterface()) {
            if (!$name = $this->getImplementation($name)) {
                throw new ContainerException("Interface $name is not instantiable.");
            }
            $reflection = new ReflectionClass($name);
        } elseif (!$reflection->isInstantiable()) {
            throw new ContainerException("Class $name is not instantiable.");
        }
        $variables = $this->_getVariables($name);
        $dependencies = $this->getDependencies($name);
        $parents = class_parents($name);
        foreach ($parents as $parentClassName) {
            $parentDependencies = $this->getDependencies($parentClassName);
            $dependencies = array_merge($dependencies, $parentDependencies);
            $parentVariables = $this->_getVariables($parentClassName);
            $variables = array_merge($variables, $parentVariables);
        }

        $service = empty($dependencies) ? $reflection->newInstance() : $reflection->newInstanceArgs($dependencies);
        
        $this->_setVariables($service, array_merge($variables, $params));

        return $service;
    }

    /**
     * @param array $params
     *
     * @return array
     * @throws ContainerException
     */
    public function getDependencies($className, $methodName = null)
    {
        $dependencies = array();
        $reflection = new ReflectionClass($className);
        if (is_null($methodName)) {
            if (
                   !$constructor = $reflection->getConstructor()
                or $constructor->getDeclaringClass()==$reflection->getParentClass()
            ) {
                return $dependencies;
            }
            $params = $constructor->getParameters();
        } else {
            if (!$method = $reflection->getMethod($methodName)) {
                return $dependencies;
            }
            $params = $method->getParameters();
        }
        foreach ($params as $param) {
            if ('params'==$param->getName()) {
                continue;
            }
            // get the type hinted class
            if (!is_null($dependency = $param->getClass())) {
                // get dependency resolved
                if ($instance = $this->get($dependency->name)) {
                    $dependencies[] = $instance;
                }
            }
        }
        return array_filter($dependencies);
    }

    /**
     * Извлечение конфигурации по полному имени класса
     * 
     * @param string $className
     * @return array
     */
    private function _getVariables($className)
    {
        if (isset($this->_config[$className])) {
            return $this->_config[$className];
        }
        return array();
    }

    /**
     * устанавливает св-ва
     * 
     * @param string $name
     * @return void
     */
    private function _setVariables($service, $config)
    {
        if (empty($config)) {
            return;
        }
        if (!empty($config['variables'])) {
            foreach ($config['variables'] as $name => $val) {
                // Устанавливает св-во объекта $service
                if (array_key_exists($name, get_object_vars($service))) {
                    $service->$name = $val;
                }
            }
        }
    }
}
