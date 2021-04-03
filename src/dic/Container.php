<?php
namespace tachyon\dic;

use
    /*Psr\Container\ContainerInterface,*/
    ReflectionClass,
    tachyon\exceptions\ContainerException,
    ReflectionException;

/**
 * Dependency Injection Container
 *
 * @author Андрей Сердюк
 * @copyright (c) 2020 IMND
 */
class Container /*implements ContainerInterface*/
{
    /**
     * Массив инстанциированных компонентов
     * @var array
     */
    protected array $services = [];

    /**
     * Конфигурация компонентов и их параметров
     * @var array
     */
    protected array $config = [];

    /**
     * Сопоставление интерфейсов и их реализаций
     * @var array
     */
    protected array $implementations = [];

    public function __construct()
    {
        $this->_loadConfig();
    }

    public function boot(): Container
    {
        return $this;
    }

    /**
     * Загружаем компоненты и параметры компонентов в массив $config
     */
    private function _loadConfig(): void
    {
        $basePath = dirname(str_replace('\\', '/', realpath(__DIR__)));
        $services = include "$basePath/dic/services.php";
        if (
               file_exists($appConfPath = "$basePath/../../app/config/services.php")
            && $appElements = include $appConfPath
        ) {
            $elements = array_merge($elements, $appElements);
        }
        foreach ($services as $service) {
            $class = $service['class'];
            if (isset($this->config[$class])) {
                continue;
            }
            $serviceConf = [
                'variables' => array(),
                'singleton' => !empty($service['singleton']),
            ];
            if (isset($service['properties'])) {
                foreach ($service['properties'] as $property) {
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
     * @param string $className
     * @param array  $params динамически назначаемые параметры
     *
     * @return mixed
     * @throws ContainerException | ReflectionException
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

    public function has($id): bool
    {
        return isset($this->services[$id]);
    }

    /**
     * Извлечение реализации интерфейса
     *
     * @param $interface
     *
     * @return mixed|null
     */
    public function getImplementation($interface)
    {
        return $this->implementations[$interface] ?? null;
    }

    /**
     * Создает экземпляр сервиса
     *
     * @param string $name
     * @param array  $params
     *
     * @return object
     * @throws ContainerException
     * @throws ReflectionException
     */
    private function resolve(string $name, array $params = array())
    {
        $implementName = $name;
        try {
            $reflection = new ReflectionClass($implementName);
        } catch (ReflectionException $e) {
            throw new ContainerException($e->getMessage());
        }
        if ($reflection->isInterface()) {
            if (!$this->getImplementation($implementName)) {
                throw new ContainerException("Interface $implementName is not instantiable.");
            }
            $implementName = $this->getImplementation($implementName);
            $reflection = new ReflectionClass($implementName);
        } elseif (!$reflection->isInstantiable()) {
            throw new ContainerException("Class $name is not instantiable.");
        }

        $variables = $this->_getVariables($implementName);
        $dependencies = $this->getDependencies($implementName);
        $parents = class_parents($implementName);

        $constructor = $reflection->getConstructor();
        foreach ($parents as $parentClassName) {
            // случай, когда у потомка нет своего конструктора
            if ($constructor !== null && $constructor->class === $parentClassName) {
                continue;
            }
            $parentDependencies = $this->getDependencies($parentClassName);
            $dependencies = array_merge($dependencies, $parentDependencies);
            $parentVariables = $this->_getVariables($parentClassName);
            $variables = array_merge($variables, $parentVariables);
        }

        if ($constructor === null) {
            $params = array();
        } elseif (!empty($dependencies)) {
            $params = array_merge($dependencies, $params);
        }
        $service = $reflection->newInstanceArgs($params);
        $this->_setVariables($service, $variables);

        return $service;
    }

    /**
     * @param string $className
     * @param string $methodName
     *
     * @return array
     * @throws ContainerException
     * @throws ReflectionException
     */
    public function getDependencies(string $className, string $methodName = null): array
    {
        $dependencies = array();
        $reflection = new ReflectionClass($className);
        if (is_null($methodName)) {
            if (
                   !$constructor = $reflection->getConstructor()
                or $constructor->getDeclaringClass()===$reflection->getParentClass()
            ) {
                return $dependencies;
            }
            $params = $constructor->getParameters();
        } elseif (!$method = $reflection->getMethod($methodName)) {
            return $dependencies;
        } else {
            $params = $method->getParameters();
        }
        foreach ($params as $param) {
            if ('params' === $param->getName()) {
                continue;
            }
            if (
                   // get the type hinted class
                   !is_null($dependency = $param->getClass())
                   // get dependency resolved
                && $instance = $this->get($dependency->name)
            ) {
                $dependencies[] = $instance;
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
    private function _getVariables($className): array
    {
        return $this->config[$className] ?? [];
    }

    /**
     * Устанавливает св-ва
     *
     * @param mixed $service
     * @param array $config
     * @return void
     */
    private function _setVariables($service, array $config): void
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
