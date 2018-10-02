<?php
namespace tachyon\dic;

use tachyon\helpers\StringHelper;

/**
 * DI Container
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

    private static function _loadConfig()
    {
        // Загружаем компоненты и параметры компонентов
        $basePath = dirname(str_replace('\\', '/', realpath(__DIR__)));
        $coreConfText = file_get_contents("$basePath/config/services.xml");
        $appConfPath = "$basePath/../../app/config/services.xml";
        if (file_exists($appConfPath)) {
            $appConfText = file_get_contents($appConfPath);
            $appConfText = str_replace('<services>', '', $appConfText);
            $coreConfText = str_replace('</services>', '', $coreConfText);
            $coreConfText .= $appConfText;
        }
        $elements = new \SimpleXMLElement($coreConfText);
        foreach ($elements as $element) {
            self::_setConfig($element);
        }
    }

    private static function _setConfig($element)
    {
        $id = (string)$element['id'];
        if (isset(self::$_config[$id]))
            return;

        $service = array(
            'class' => (string)$element['class'],
            'services' => array(),
            'variables' => array(),
            'singleton' => ('true' === (string)$element['singleton']),
        );
        foreach ($element->property as $property) {
            $propName = (string)$property['name'];
            if ($ref = $property->ref) {
                $subService = array(
                    'id' => (string)$ref['id'],
                    'variables' => array(),
                );
                foreach ($property->property as $subProperty) {
                    $value = (string)$subProperty->value;
                    if (strpos($value, '[')!==false) {
                        $value = str_replace(array('[', ']'), '', $value);
                        $value = explode(',', $value);
                    }
                    $subService['variables'][(string)$subProperty['name']] = $value;
                }
                $service['services'][$propName] = $subService;
            } elseif ($val = $property->value) {
                $service['variables'][$propName] = (string)$val;
            }
        }
        self::$_config[$id] = $service;
    }

    /**
     * @param string $name
     * @param array $params динамически назначаемые параметры
     * @return mixed
     */
    public static function getInstanceOf($name, $domain = null, array $params = array())
    {
        if (!self::$_initialised) {
            self::_loadConfig();
            self::$_initialised = true;
        }

        $config = self::$_config[$name];

        $config['variables']['domain'] = $domain;
        if (!empty($config['singleton'])) {
            if (!isset(self::$_services[$name])) {
                self::$_services[$name] = self::_createService($config, $params);
            }
            return self::$_services[$name];
        }
        return self::_createService($config, $params);
    }

    private static function _createService($config, array $params = array())
    {
        $className = self::_getConfigParam($config, 'class');
        $service = new $className($params);

        self::_setProperties($service, $config);

        $parents = class_parents($service);
        foreach ($parents as $parentClassName) {
            $id = StringHelper::getShortClassName($parentClassName);
            if (!isset(self::$_config[$id]))
                continue;

            $parentConfig = self::$_config[$id];
            if (isset($config['domain'])) {
                $parentConfig['variables']['domain'] = $config['domain'];
            }
            self::_setProperties($service, $parentConfig);
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
                $subServConfig['variables']['domain'] = $service;
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
     * @throws \ErrorException
     */
    private static function _setProperty($service, $name, $val)
    {
        if (array_key_exists($name, get_object_vars($service))) {
            $service->$name = $val;
            return;
        }
        $setterMethod = 'set' . ucfirst($name);
        if (method_exists($service, $setterMethod)) {
            $service->$setterMethod($val);
            return;
        }
        //throw new \ErrorException("Невозможно установить свойство $name в сервис " . get_class($service));
    }

    private static function _getConfigParam($config, $param)
    {
        if (isset($config[$param]))
            return $config[$param];
    }

    private static function _setConfigParam(&$config, $param, $val)
    {
        $config[$param] = $val;
    }
}
