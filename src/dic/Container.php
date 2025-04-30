<?php

namespace tachyon\dic;

use
    ReflectionClass,
    tachyon\exceptions\ContainerException,
    ReflectionException;

/**
 * Dependency Injection Container
 *
 * @author imndsu@gmail.com
 */
abstract class Container
{
    /**
     * instantiated components array
     */
    protected array $services = [];

    /**
     * configuration of the components and their parameters
     */
    protected array $config = [];

    /**
     * comparison of the interfaces and their implementations
     */
    protected array $implementations = [];

    public function __construct()
    {
        // load components and parameters of components to the $config array
        $basePath = dirname(str_replace('\\', '/', realpath(__DIR__)));
        $services = include "$basePath/dic/services.php";
        if (
               file_exists($appConfPath = "$basePath/../../../../app/config/services.php")
            && $appServices = include $appConfPath
        ) {
            $services = array_merge($services, $appServices);
        }
        foreach ($services as $service) {
            $class = $service['class'];
            if (isset($this->config[$class])) {
                continue;
            }
            $serviceConf = [
                'variables' => [],
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

    abstract public function boot($params = []): Container;

    /**
     * creates an instance of the service
     *
     * @param string $className
     * @param array $params dynamically assigned parameters
     *
     * @return mixed
     * @throws ContainerException
     * @throws ReflectionException
     */
    public function get(string $className, array $params = []): mixed
    {
        if (
                $config = $this->getVariables($className)
            and !empty($config['singleton'])
        ) {
            return $this->singleton($className, $params);
        }
        return $this->resolve($className, $params);
    }

    /**
     * creates an instance of the singleton
     *
     * @param string $className
     * @param array  $params dynamically assigned parameters
     *
     * @return mixed
     * @throws ContainerException | ReflectionException
     */
    public function singleton(string $className, array $params = [])
    {
        if (!isset($this->services[$className])) {
            $this->services[$className] = $this->resolve($className, $params);
        }
        return $this->services[$className];
    }

    public function has($id): bool
    {
        return isset($this->services[$id]);
    }

    /**
     * extraction of interface implementation
     */
    private function getImplementation(string $interface): mixed
    {
        return $this->implementations[$interface] ?? null;
    }

    /**
     * creates an instance of the service
     *
     * @throws ContainerException | ReflectionException
     */
    private function resolve(string $name, array $params = [])
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
        $variables = $this->getVariables($implementName);
        $dependencies = $this->getDependencies($implementName);
        $parents = class_parents($implementName);
        $constructor = $reflection->getConstructor();
        foreach ($parents as $parentClassName) {
            // descendant class has no constructor
            if ($constructor !== null && $constructor->class === $parentClassName) {
                continue;
            }
            $parentDependencies = $this->getDependencies($parentClassName);
            $dependencies = array_merge($dependencies, $parentDependencies);
            $parentVariables = $this->getVariables($parentClassName);
            $variables = array_merge($variables, $parentVariables);
        }
        if ($constructor === null) {
            $params = [];
        } elseif (!empty($dependencies)) {
            $params = array_merge($dependencies, $params);
        }
        $service = $reflection->newInstanceArgs($params);
        $this->setParameters($service, $variables);

        return $service;
    }

    /**
     * @throws ContainerException
     * @throws ReflectionException
     */
    public function getDependencies(string $className, string $methodName = null): array
    {
        $dependencies = [];
        $reflection = new ReflectionClass($className);
        if (is_null($methodName)) {
            if (
                !$constructor = $reflection->getConstructor()
                or $constructor->getDeclaringClass() === $reflection->getParentClass()
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
                    $dependencyType = $param->getType()
                and !$dependencyType->isBuiltin()
                // get dependency resolved
                and $instance = $this->get($dependencyType->getName())
            ) {
                $dependencies[] = $instance;
            }
        }
        return array_filter($dependencies);
    }

    /**
     * get the configuration by full class name
     */
    private function getVariables(string $className): array
    {
        return $this->config[$className] ?? [];
    }

    /**
     * set the properties
     */
    private function setParameters(mixed $service, array $config): void
    {
        if (empty($config)) {
            return;
        }
        if (!empty($config['variables'])) {
            foreach ($config['variables'] as $name => $val) {
                // set the property of $service
                if (array_key_exists($name, get_object_vars($service))) {
                    $service->$name = $val;
                }
            }
        }
    }
}
