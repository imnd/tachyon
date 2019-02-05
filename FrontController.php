<?php
namespace tachyon;

use Exception;
use ErrorException;
use BadMethodCallException;
use tachyon\exceptions\ContainerException;
use tachyon\exceptions\HttpException;

use tachyon\helpers\ArrayHelper;

/**
 * Front Controller приложения
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
final class FrontController extends Component
{
    # сеттеры сервисов, которые внедряются в компонент
    use \tachyon\dic\OutputCache;
    use \tachyon\dic\Message;
    use \tachyon\dic\View;

    /**
     * @var string контроллер по умолчанию
     */
    private $defaultController;

    /**
     * Обработка входящего запроса
     * и передача управления соотв. контроллеру
     */
    public function dispatch()
    {
        $requestUri = $_SERVER['REQUEST_URI'];

        // кеширование
        $this->cache->start($requestUri);

        // разбираем запрос
        $requestVars = [
            'get' => $_GET,
            'post' => $_POST,
            'files' => $_FILES,
        ];
        $urlInfo = parse_url($requestUri);
        $requestArr = explode('/', $urlInfo['path']);
        array_shift($requestArr);
        $this->defaultController = $this->config->getOption('defaultController') ?: 'Index';
        // Извлекаем имя контроллера
        $controllerName = $this->_getNameFromRequest($requestArr, $this->defaultController) . 'Controller';
        // Извлекаем имя экшна
        $actionName = $this->_getNameFromRequest($requestArr, 'index');
        // разбираем массив параметров
        $requestVars = array_merge_recursive($requestVars, $this->_parseRequest($requestArr));
        foreach (['get', 'post', 'inline'] as $key) {
            $requestVars[$key] = $this->_filterVars($requestVars, $key);
        }
        // запускаем соотв. контроллер
        $this->_startController($controllerName, $actionName, $requestVars);
        // кеширование
        $this->cache->end();
    }

    /**
     * Извлечение имени контроллера или экшна
     * 
     * @param $requestArr array Массив параметров
     * @param $default string имя по умолчанию
     * 
     * @return string
     */
    private function _getNameFromRequest(array &$requestArr, $default): string
    {
        if (
                count($requestArr)===0
            || is_numeric($requestArr[0])
            or '' === $name = array_shift($requestArr)
        ) {
            return $default;
        }
        return $name;
    }

    /**
     * @param $requestArr array
     * 
     * @return array
     */
    private function _parseRequest(array $requestArr)
    {
        $requestVars = ['get' => array()];
        if (!empty($requestArr)) {
            $requestArr = array_chunk($requestArr, 2);
            foreach ($requestArr as $pair) {
                if (isset($pair[1])) {
                    $requestVars['get'][$pair[0]] = urldecode($pair[1]);
                } elseif ($pair[0]!=='') {
                    $requestVars['inline'] = $pair[0];
                }
            }
        }
        return $requestVars;
    }

    /**
     * Защита от XSS и SQL injection
     * @param array $vars
     * @return void
     */
    private function _filterVars($vars, $key)
    {
        if (!isset($vars[$key])) {
            return;
        }
        $arr = $vars[$key];
        if (is_string($arr)) {
            $arr = ArrayHelper::filterText($arr);
        } elseif (is_array($arr)) {
            foreach ($arr as &$value) {
                $value = ArrayHelper::filterText($value);
            }
            $arr = array_filter($arr);
        }
        return $arr;
    }

    /**
     * запускаем контроллер
     */
    private function _startController($controllerName, $actionName, $requestVars)
    {
        try {
            // инициализация
            $controller = $this->_getController($controllerName, $actionName);
            $controller->start($requestVars);
            // запускаем
            $controller->beforeAction();
            $inlineVars = isset($requestVars['inline']) ? $requestVars['inline'] : null;
            if (!method_exists($controller, $actionName)) {
                throw new BadMethodCallException;
            }
            $controller->$actionName($inlineVars);
            $controller->afterAction();
        } catch (BadMethodCallException $e) {
            $this->_error(404, "Экшн \"$actionName\" нет в контроллере \"$controllerName\" не найден");
        } catch (ContainerException $e) {
            $this->_error(404, "Контроллер \"$controllerName\" не найден. {$e->getMessage()}");
        } catch (ErrorException $e) {
            $this->_error(404, 'Путь не найден');
        } catch (HttpException $e) {
            $this->_error($e->getCode(), $e->getMessage());
        } catch (Exception $e) {
            $this->_error(404, $e->getMessage());
        }
    }

    /**
     * Инициализация
     * 
     * @param string $controllerName
     * @return Controller
     */
    private function _getController(string $controllerName, $actionName)
    {
        $controller = $this->get($controllerName);
        $controller
            ->setAction($actionName)
            ->setId(str_replace('Controller', '', lcfirst($controller->getClassName())));

        return $controller;
    }

    /**
     * обработчик неправильного запроса
     */
    private function _error($code, $error)
    {
        http_response_code($code);
        $controller = $this->_getController($this->defaultController . 'Controller', 'error');
        $controller->start();
        $controller->layout('error', compact('code', 'error'));
        die;
    }
}
