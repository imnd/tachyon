<?php
namespace tachyon;

use Error;
use ErrorException;
use
    ReflectionClass,
    tachyon\helpers\ArrayHelper,
    // exceptions
    BadMethodCallException,
    tachyon\exceptions\ContainerException,
    tachyon\exceptions\HttpException,
    // dependencies
    tachyon\dic\Container,
    tachyon\cache\Output,
    tachyon\components\Message
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
     * @param Config $config
     * @param Output $cache
     * @param Message $msg
     * @param View $view
     * @param Container $container
     */
    public function __construct(
        Config $config,
        Output $cache,
        Message $msg,
        View $view,
        Container $container
    )
    {
        $this->config    = $config;
        $this->cache     = $cache;
        $this->msg       = $msg;
        $this->view      = $view;
        $this->container = $container;
        $this->routes    = $this->config->get('routes');
    }

    /**
     * Обработка входящего запроса
     * и передача управления соответствующему контроллеру
     *
     * @throws ContainerException
     * @throws \ReflectionException
     */
    public function dispatch()
    {
        $requestUri = $_SERVER['REQUEST_URI'];
        // кеширование
        $this->cache->start($requestUri);
        // разбираем запрос

        list($path) = array_values(parse_url($requestUri));

        // массив параметров
        $requestVars = [
            'get'   => $_GET,
            'post'  => $_POST,
            'files' => $_FILES,
        ];
        if (isset($this->routes[$path])) {
            $route = $this->_parseRoute($path);
        } else {
            $requestArr = explode('/', $path);
            array_shift($requestArr);
            // Извлекаем имя контроллера и экшна
            $route = [
                'controller' => $this->_getNameFromRequest($requestArr),
                'action'     => $this->_getNameFromRequest($requestArr)
            ];
            $route['controller'] = '\app\controllers\\' . ucfirst($route['controller']) . 'Controller';
            // разбираем массив параметров
            $inlineVars = $this->_parseRequest($requestArr);
            $requestVars = array_merge_recursive($requestVars, $inlineVars);
        }
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
     * Extract controller and action names from conf route
     * 
     * @param string $path
     * @return array
     */
    private function _parseRoute($path)
    {
        $pathArr = explode('@', $this->routes[$path]);
        return [
            'controller' => $pathArr[0],
            'action'     => $pathArr[1]
        ];
    }

    /**
     * Извлечение имени контроллера или экшна
     *
     * @param $requestArr array Массив параметров
     * @return string|null
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
    private function _parseRequest(array $requestArr): array
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
     *
     * @param array  $vars
     * @param string $key
     *
     * @return mixed
     */
    private function _filterVars(array $vars, string $key)
    {
        if (!isset($vars[$key])) {
            return null;
        }
        return $this->_filterVar($vars[$key]);
    }

    /**
     * Защита от XSS и SQL injection
     *
     * @param mixed $arr
     *
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
     * Запускаем контроллер
     *
     * @param array $route
     * @param array $requestVars
     *
     * @throws ContainerException
     * @throws \ReflectionException
     */
    private function _startController(array $route, array $requestVars)
    {
        try {
            if (empty($route['controller'])) {
                if (!isset($this->routes['default'])) {
                    throw new HttpException('Wrong url');
                }
                $route = $this->_parseRoute('default');
            }
            $controllerClass = $route['controller'];
            $controller = $this->container->get($controllerClass);
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

            $actionVars = $this->container->getDependencies($controllerClass, $actionName);
            if (!is_null($requestVars['inline'])) {
                $actionVars[] = $requestVars['inline'];
            }
            $controller->$actionName(...$actionVars);
            $controller->afterAction();
        } catch (BadMethodCallException $e) {
            $this->_error(HttpException::NOT_FOUND, $this->msg->i18n('There is no action "%actionName" in controller "%controllerName".', compact('controllerName', 'actionName')));
        } catch (HttpException | ContainerException $e) {
            $this->_error(HttpException::NOT_FOUND, $this->msg->i18n('Not found.'));
        } catch (ErrorException | Error $e) {
            $this->_error($e->getCode(), $e->getMessage());
        }
    }

    /**
     * Обработчик неправильного запроса
     * Вывод сообщения об ошибке
     *
     * @param integer $code код ошибки
     * @param string $msg текст ошибки
     *
     * @return void
     * @throws \ReflectionException
     */
    private function _error($code, $msg)
    {
        http_response_code($code);

        if (!isset($this->routes['error'])) {
            echo("$code: $msg");
            die;
        }
        $controllerClass = $this->_parseRoute('error')['controller'];
        $controllerId = lcfirst(str_replace('Controller', '', (new ReflectionClass($controllerClass))->getShortName()));

        $this->container
            ->get($controllerClass)
            ->setAction('error')
            ->setId($controllerId)
            ->start()
            ->view('error', compact('code', 'msg'));

        die;
    }
}
