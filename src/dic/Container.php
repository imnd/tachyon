<?php
namespace tachyon\dic;

use ReflectionClass,
    ErrorException,
    tachyon\exceptions\ContainerException;

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
     * @var array
     */
    protected $services = array();
    /**
     * Конфигурация компонентов и их параметров
     * @var array
     */
    protected $config = array();
    /**
     * Сопоставление интерфейсов и их реализаций
     * @var array
     */
    protected $implementations;

    public function __construct()
    {
        $this->_loadConfig();

        defined('APP_ENV') or define('APP_ENV', 'prod');
    }

    public function boot()
    {
    }

    /**
     * Загружаем компоненты и параметры компонентов в массив $config
     */
    private function _loadConfig()
    {
        $basePath = dirname(str_replace('\\', '/', realpath(__DIR__)));
        $elements = include "$basePath/dic/services.php";
        if (
                file_exists($appConfPath = "$basePath/../../app/config/services.php")
            and $appElements = include $appConfPath
        ) {
            $elements = array_merge($elements, $appElements);
        }
        foreach ($elements as $element) {
            $class = $element['class'];
            if (isset($this->config[$class])) {
                continue;
            }
            $serviceConf = [
                'variables' => array(),
                'singleton' => !empty($element['singleton']),
            ];
            if (isset($element['properties'])) {
                foreach ($element['properties'] as $property) {
                    $propName = $property['name'];
                    if (!empty($property['value'])) {
                        $serviceConf['variables'][$propName] = $property['value'];
                    }
                }
            }
            $this->config[$class] = $serviceConf;
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
            if (!isset($this->services[$className])) {
                $this->services[$className] = $this->resolve($className, $params);
            }
            return $this->services[$className];
        }
        return $this->resolve($className, $params);
    }

    public function has($name)
    {
        return isset($this->services[$name]);
    }

    /**
     * Извлечение реализации интерфейса
     */
    public function getImplementation($interface)
    {
        return $this->implementations[$interface] ?? null;
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
        try {
            $reflection = new ReflectionClass($name);
        } catch (ErrorException $e) {
            throw new ContainerException($e->getMessage());
        }
        if ($reflection->isInterface()) {
            if (!$this->getImplementation($name)) {
                throw new ContainerException("Interface $name is not instantiable.");
            }
            $name = $this->getImplementation($name);
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

        if (empty($reflection->getConstructor())) {
            $params = array();
        } else {
            if (!empty($dependencies)) {
                $params = array_merge($dependencies, $params);
            }
        }
        try {
            $service = $reflection->newInstanceArgs($params);
        } catch (ErrorException $e) {
            throw new ContainerException($e->getMessage());
        }

        $this->_setVariables($service, $variables);

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
        if (isset($this->config[$className])) {
            return $this->config[$className];
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
