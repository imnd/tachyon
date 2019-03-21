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
     * @var tachyon\Config $config
     */
    protected $config;
    /**
     * @var \tachyon\cache\Output $cache
     */
    protected $cache;
    /**
     * @var \tachyon\components\Message $msg
     */
    protected $msg;
    /**
     * @var \tachyon\View $view
     */
    protected $view;

    /**
     * @var string контроллер по умолчанию
     */
    private $defaultController;

    /**
     * @param boolean string integer array mixed 
     */
    public function __construct(Config $config, Output $cache, Message $msg, View $view)
    {
        $this->config = $config;
        $this->cache = $cache;
        $this->msg = $msg;
        $this->view = $view;
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
        $this->defaultController = $this->config->get('defaultController') ?: 'app\controllers\IndexController';
        // Извлекаем имя контроллера
        $controllerName = $this->_getNameFromRequest($requestArr, $this->defaultController);
        // Извлекаем имя экшна
        $actionName = $this->_getNameFromRequest($requestArr, 'index');
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
        $requestVars = array('get' => array());
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
            $controller = (new Container)->get('\app\controllers\\' . ucfirst($controllerName) . 'Controller');
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

            $controller->$actionName($requestVars['inline'] ?? null);
            $controller->afterAction();
        } catch (ContainerException $e) {
            $this->_error(HttpException::NOT_FOUND, $this->msg->i18n('Controller "%controllerName" is not found.', compact('controllerName')));
        } catch (BadMethodCallException $e) {
            $this->_error(HttpException::NOT_FOUND, $this->msg->i18n('There is no action "%actionName" in controller "%controllerName".', compact('controllerName', 'actionName')));
        } catch (HttpException $e) {
            $this->_error($e->getCode(), $e->getMessage());
        /*} catch (ErrorException $e) {
            $this->_error($e->getCode(), $e->getMessage());*/
        /*} catch (Error $e) {
            $this->_error($e->getCode(), $e->getMessage());*/
        }
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

        $id = lcfirst(str_replace('Controller', '', (new ReflectionClass($this->defaultController))->getShortName()));
        (new Container)
            ->get($this->defaultController)
            ->setAction('error')
            ->setId($id)
            ->start()
            ->layout('error', compact('code', 'msg'));

        die;
    }
}
