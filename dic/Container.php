<?php
namespace tachyon\dic;

use tachyon\helpers\StringHelper,
    tachyon\exceptions\ContainerException;

/**
 * Dependency Injection Container
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
class Container
{
    private static $_initialised = false;
    /**
     * массив инстанциированных компонентов
     */
    private static $_services = array();
    /**
     * конфигурация компонентов и их параметров
     * @var $parameters array
     */
    private static $_config = array();

    /**
     * Создает экземпляр сервиса
     * 
     * @param string $name
     * @param mixed $owner объект "хозяин" сервиса
     * @param array $params динамически назначаемые параметры
     * @return mixed
     */
    public static function getInstanceOf($name, $owner = null, array $params = array())
    {
        if (!self::$_initialised) {
            self::_loadConfig();
            self::$_initialised = true;
        }
        if (!isset(self::$_config[$name])) {
            throw new ContainerException($this->msg->i18n('Class did not found in config file.'));
        }
        $config = self::$_config[$name];

        $config['variables']['owner'] = $owner;
        if (!empty($config['singleton'])) {
            if (!isset(self::$_services[$name])) {
                self::$_services[$name] = self::_createService($config, $params);
            }
            return self::$_services[$name];
        }
        return self::_createService($config, $params);
    }

    /**
     * Загружаем компоненты и параметры компонентов в массив $_config
     */
    private static function _loadConfig()
    {
        $basePath = dirname(str_replace('\\', '/', realpath(__DIR__)));
        $coreConfText = file_get_contents("$basePath/dic/services.json");
        $elements = json_decode($coreConfText, true);
        if (
                file_exists($appConfPath = "$basePath/../../app/dic/services.json")
            and $appConfText = file_get_contents($appConfPath)
            and $appElements = json_decode($appConfText, true)
        ) {
            $elements = array_merge($elements, $appElements);
        }
        foreach ($elements as $element) {
            $id = $element['id'];
            if (isset(self::$_config[$id])) {
                continue;
            }
            $service = [
                'class' => $element['class'],
                'services' => array(),
                'variables' => array(),
                'singleton' => (isset($element['singleton']) && 'true' === $element['singleton']),
            ];
            if (isset($element['properties'])) {
                foreach ($element['properties'] as $property) {
                    $propName = $property['name'];
                    if (isset($property['ref'])) {
                        $subService = [
                            'id' => $property['ref'],
                            'variables' => array(),
                        ];
                        if (isset($property['properties'])) {
                            foreach ($property['properties'] as $subProperty) {
                                $value = $subProperty['value'];
                                if (strpos($value, '[')!==false) {
                                    $value = str_replace(array('[', ']'), '', $value);
                                    $value = explode(',', $value);
                                }
                                $subService['variables'][$subProperty['name']] = $value;
                            }
                        }
                        $service['services'][$propName] = $subService;
                    } elseif ($val = $property['value']) {
                        $service['variables'][$propName] = $val;
                    }
                }
            }
            self::$_config[$id] = $service;
        }
    }

    /**
     * Создает экземпляр сервиса
     * 
     * @param array $config
     * @param array $params
     * @return void
     * @throws ContainerException
     */
    private static function _createService(array $config, array $params = array())
    {
        if (!$className = self::_getConfigParam($config, 'class')) {
            throw new ContainerException($this->msg->i18n('Class did not found in config file.'));
        }
        $service = new $className($params);

        self::_setProperties($service, $config);

        $parents = class_parents($service);
        foreach ($parents as $parentClassName) {
            $id = StringHelper::getShortClassName($parentClassName);
            if (!isset(self::$_config[$id])) {
                continue;
            }
            $parentConfig = self::$_config[$id];
            if (isset($config['owner'])) {
                $parentConfig['variables']['owner'] = $config['owner'];
            }
            self::_setProperties($service, $parentConfig);
        }
        if (method_exists($service, 'initialize')) {
            $service->initialize();
        }
        return $service;
    }

    /**
     * устанавливает св-ва
     * 
     * @param string $name
     * @return void
     */
    private static function _setProperties($service, $config)
    {
        if ($variables = self::_getConfigParam($config, 'variables')) {
            foreach ($variables as $name => $val) {
                self::_setProperty($service, $name, $val);
            }
        }
        if ($subServices = self::_getConfigParam($config, 'services')) {
            foreach ($subServices as $name => $subServiceConf) {
                $subServConfig = self::$_config[$subServiceConf['id']];
                $subServConfig['variables'] = array_merge($subServConfig['variables'], $subServiceConf['variables']);
                $subServConfig['variables']['owner'] = $service;
                $subService = self::_createService($subServConfig);
                self::_setProperty($service, $name, $subService);
            }
        }
    }

    /**
     * Устанавливает св-во объекта $service
     * 
     * @param mixed $service
     * @param string $name
     * @param mixed $val
     * @return void
     * @throws ContainerException
     */
    private static function _setProperty($service, $name, $val)
    {
        try {
            if (array_key_exists($name, get_object_vars($service))) {
                $service->$name = $val;
                return;
            }
            $setterMethod = 'set' . ucfirst($name);
            if (method_exists($service, $setterMethod)) {
                $service->$setterMethod($val);
                return;
            }
            if ($name!=='owner') {
                throw new ContainerException($this->msg->i18n('Unable to set property %property to service %service.', [
                    'property' => $name,
                    'service' => get_class($service),
                ]));
            }
        } catch (ContainerException $e) {
            echo $e->getMessage();
            die;
        }
    }

    private static function _getConfigParam($config, $param)
    {
        if (isset($config[$param])) {
            return $config[$param];
        }
    }

    private static function _setConfigParam(&$config, $param, $val)
    {
        $config[$param] = $val;
    }
}
