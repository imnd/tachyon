<?php
namespace tachyon;

use 
    ReflectionClass,
    tachyon\helpers\ArrayHelper,
    // exceptions
    Error,
    ErrorException,
    BadMethodCallException,
    tachyon\exceptions\ContainerException,
    tachyon\exceptions\HttpException,
    // dependencies
    tachyon\dic\Container,
    tachyon\Config,
    tachyon\cache\Output,
    tachyon\components\Message,
    tachyon\View
;

/**
 * Front Controller приложения
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
final class Router
{
    /**
     * @var Config $config
     */
    private $config;
    /**
     * @var \tachyon\cache\Output $cache
     */
    private $cache;
    /**
     * @var \tachyon\components\Message $msg
     */
    private $msg;
    /**
     * @var View $view
     */
    private $view;
    /**
     * @var Container $container
     */
    private $container;
    /**
     * @var array $routes
     */
    private $routes;

    /**
     * @var string контроллер по умолчанию
     */
    private $defaultController = '\app\controllers\IndexController';

    /**
     * @param boolean string integer array mixed 
     */
    public function __construct(
        Config $config,
        Output $cache,
        Message $msg,
        View $view,
        Container $container
    )
    {
        $this->config = $config;
        $this->cache = $cache;
        $this->msg = $msg;
        $this->view = $view;
        $this->container = $container;

        $basePath = dirname(str_replace('\\', '/', realpath(__DIR__)));
        $this->routes = require("$basePath/../app/config/routes.php");
    }

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
        $urlInfo = parse_url($requestUri);
        $requestArr = explode('/', $urlInfo['path']);
        array_shift($requestArr);
        // Извлекаем имя контроллера и экшна
        $route = [
            'controller' => $this->_getNameFromRequest($requestArr),
            'action' => $this->_getNameFromRequest($requestArr)
        ];
        // разбираем массив параметров
        $requestVars = array_merge_recursive([
            'get' => $_GET,
            'post' => $_POST,
            'files' => $_FILES,
        ], $this->_parseRequest($requestArr));
        // фильтруем
        foreach (['get', 'post', 'inline'] as $key) {
            $requestVars[$key] = $this->_filterVars($requestVars, $key);
        }
        // запускаем соотв. контроллер
        $this->_startController($route, $requestVars);
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
    private function _getNameFromRequest(array &$requestArr)
    {
        if (
                count($requestArr)===0
            || is_numeric($requestArr[0])
        ) {
            return;
        }
        return array_shift($requestArr);
    }

    /**
     * @param $requestArr array
     * 
     * @return array
     */
    private function _parseRequest(array $requestArr)
    {
        $requestVars = array('get' => array());
        if (!empty($requestArr)) {
            $requestVars['inline'] = array_shift($requestArr);
            $requestArr = array_chunk($requestArr, 2);
            foreach ($requestArr as $pair) {
                if (isset($pair[1])) {
                    $requestVars['get'][$pair[0]] = urldecode($pair[1]);
                }
            }
        }
        return $requestVars;
    }

    /**
     * Защита от XSS и SQL injection
     * @param array $vars
     * @return mixed
     */
    private function _filterVars($vars, $key)
    {
        if (!isset($vars[$key])) {
            return;
        }
        return $this->_filterVar($vars[$key]);
    }

    /**
     * Защита от XSS и SQL injection
     * @param mixed $arr
     * @return mixed
     */
    private function _filterVar($arr)
    {
        if (is_string($arr)) {
            return ArrayHelper::filterText($arr);
        } elseif (is_array($arr)) {
            foreach ($arr as &$val) {
                $val = $this->_filterVar($val);
            }
            return array_filter($arr);
        }
    }

    /**
     * запускаем контроллер
     */
    private function _startController($route, $requestVars)
    {
        if (isset($this->routes['default'])) {
            $this->defaultController = $this->routes['default'];
        }
        try {
            if (empty($controllerName = $route['controller'])) {
                $controllerClassName = $this->defaultController;
            } else {
                $controllerClassName = $this->routes[$controllerName] ?? '\app\controllers\\' . ucfirst($controllerName) . 'Controller';
            }
            $controller = $this->container->get($controllerClassName);
            $controllerName = lcfirst(str_replace('Controller', '', $controller->getClassName()));
            if (empty($actionName = $route['action'])) {
                $actionName = $controller->getDefaultAction();
            }
            if (!method_exists($controller, $actionName)) {
                throw new BadMethodCallException;
            }
            $controller
                ->setAction($actionName)
                ->setId($controllerName)
                // запускаем
                ->start($requestVars)
                // инициализация
                ->init();

            if (!$controller->beforeAction()) {
                throw new HttpException($this->msg->i18n('Method "beforeAction" returned false'), HttpException::BAD_REQUEST);
            }
            // всё в порядке, отдаём страницу
            header('HTTP/1.1 200 OK');
            // защита от кликджекинга
            header('X-Frame-Options:sameorigin');
            // Защита от XSS. HTTP Only
            ini_set('session.cookie_httponly', 1);

            $actionVars = $this->container->getDependencies($controllerClassName, $actionName);
            if (!is_null($requestVars['inline'])) {
                $actionVars[] = $requestVars['inline'];
            }
            $controller->$actionName(...$actionVars);
            $controller->afterAction();
        } catch (BadMethodCallException $e) {
            $this->_error(HttpException::NOT_FOUND, $this->msg->i18n('There is no action "%actionName" in controller "%controllerName".', compact('controllerName', 'actionName')));
        } catch (HttpException $e) {
            $this->_error($e->getCode(), $e->getMessage());
        }/* catch (ContainerException $e) {
            $this->_error(HttpException::NOT_FOUND, $this->msg->i18n('Controller "%controllerName" is not found.', compact('controllerName')));
        } catch (ErrorException $e) {
            $this->_error($e->getCode(), $e->getMessage());
        } catch (Error $e) {
            $this->_error($e->getCode(), $e->getMessage());
        }*/
    }

    /**
     * Обработчик неправильного запроса
     * Вывод сообщения об ошибке
     * 
     * @param integer $code код ошибки
     * @param string $error текст сообщения
     * @return void
     */
    private function _error($code, $msg)
    {
        http_response_code($code);

        $controllerId = lcfirst(str_replace('Controller', '', (new ReflectionClass($this->defaultController))->getShortName()));
        $this->container
            ->get($this->defaultController)
            ->setAction('error')
            ->setId($controllerId)
            ->start()
            ->layout('error', compact('code', 'msg'));

        die;
    }
}
