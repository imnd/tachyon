<?php
namespace tachyon;

use
    ReflectionClass,
    // exceptions
    Error,
    ReflectionException,
    BadMethodCallException,
    tachyon\exceptions\ContainerException,
    tachyon\exceptions\HttpException,
    // dependencies
    app\ServiceContainer,
    tachyon\cache\Output as OutputCache,
    tachyon\components\Message
;
use tachyon\exceptions\ViewException;

/**
 * Front Controller приложения
 *
 * @author Андрей Сердюк
 * @copyright (c) 2020 IMND
 */
final class Router
{
    /**
     * @var Config $config
     */
    private Config $config;
    /**
     * @var OutputCache $cache
     */
    private OutputCache $cache;
    /**
     * @var Message $msg
     */
    private Message $msg;
    /**
     * @var View $view
     */
    private View $view;
    /**
     * @var ServiceContainer $container
     */
    private ServiceContainer $container;
    /**
     * @var array $routes
     */
    private $routes;

    /**
     * @param Config $config
     * @param OutputCache $cache
     * @param Message $msg
     * @param View $view
     * @param ServiceContainer $container
     */
    public function __construct(
        Config $config,
        OutputCache $cache,
        Message $msg,
        View $view,
        ServiceContainer $container
    ) {
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
     * @throws ReflectionException
     */
    public function dispatch(): void
    {
        // кеширование
        $this->cache->start($_SERVER['REQUEST_URI']);

        Request::set('get', $_GET);
        Request::set('post', $_POST);
        Request::set('files', $_FILES);

        // разбираем запрос
        $path = Request::parseUri();
        if (!$this->_parseRoute($path)) {
            $requestArr = explode('/', $path);
            // Извлекаем имя контроллера и экшна
            Request::set('controller', 'app\controllers\\' . ucfirst($this->_getNameFromRequest($requestArr)) . 'Controller');
            Request::set('action', $this->_getNameFromRequest($requestArr));
            // Разбираем массив параметров
            if (!empty($requestArr)) {
                Request::set('inline', array_shift($requestArr));
                $requestArr = array_chunk($requestArr, 2);
                foreach ($requestArr as $pair) {
                    if (isset($pair[1])) {
                        Request::add('get', [$pair[0] => urldecode($pair[1])]);
                    }
                }
            }
        }

        $this->container->boot();

        // запускаем контроллер
        $this->_startController();

        // кеширование
        $this->cache->end();
    }

    /**
     * Extract controller and action names from conf route
     *
     * @param string $path
     *
     * @return bool
     */
    private function _parseRoute($path): bool
    {
        if (!isset($this->routes[$path])) {
           return false;
        }
        $pathArr = explode('@', $this->routes[$path]);

        Request::set('controller', $pathArr[0]);
        Request::set('action', $pathArr[1]);

        return true;
    }

    /**
     * Извлечение имени контроллера или экшна
     *
     * @param $requestArr array Массив параметров
     * @return string|null
     */
    private function _getNameFromRequest(array &$requestArr): ?string
    {
        if (
               count($requestArr)===0
            || is_numeric($requestArr[0])
        ) {
            return null;
        }
        return array_shift($requestArr);
    }

    /**
     * Запускаем контроллер
     */
    private function _startController(): void
    {
        try {
            if (!$controllerClass = Request::get('controller')) {
                throw new HttpException('Wrong url');
            }
            $controller = $this->container->get($controllerClass);
            $controllerName = lcfirst(str_replace('Controller', '', $controller->getClassName()));
            if (empty($actionName = Request::get('action'))) {
                $actionName = $controller->getDefaultAction();
            }
            if (!method_exists($controller, $actionName)) {
                throw new BadMethodCallException;
            }
            $controller
                ->setAction($actionName)
                ->setId($controllerName)
                // запускаем
                ->start()
                // инициализация
                ->init();

            if (!$controller->beforeAction()) {
                throw new HttpException($this->msg->i18n('Method "beforeAction" returned false'), HttpException::BAD_REQUEST);
            }

            ob_start();
            $actionVars = $this->container->getDependencies($controllerClass, $actionName);
            if (!is_null($inline = Request::get('inline'))) {
                $actionVars[] = $inline;
            }
            $controller->$actionName(...$actionVars);
            $controller->afterAction();

            // всё в порядке, отдаём страницу
            header('HTTP/1.1 200 OK');
            // защита от кликджекинга
            header('X-Frame-Options:sameorigin');
            // Защита от XSS. HTTP Only
            ini_set('session.cookie_httponly', 1);

            echo ob_get_clean();
        } catch (BadMethodCallException $e) {
            $this->_error(HttpException::NOT_FOUND, $this->msg->i18n('There is no action "%actionName" in controller "%controllerName".', compact('controllerName', 'actionName')));
        } catch (HttpException | ViewException $e) {
            $this->_error($e->getCode(), $e->getMessage());
        } /*catch (ReflectionException | ContainerException $e) {
            $this->_error(
                HttpException::INTERNAL_SERVER_ERROR,
                $this->config->get('env')==='prod' ? $this->msg->i18n('Some error occurs') : "
                    Message: {$e->getMessage()}<br/>
                    File: {$e->getFile()}<br/>
                    Line: {$e->getLine()}
                "
            );
        } catch (Error $e) {
            $this->_error(500, $e->getMessage());
        }*/
    }

    /**
     * Обработчик неправильного запроса. Вывод сообщения об ошибке
     *
     * @param integer $code код ошибки
     * @param string  $msg  текст ошибки
     *
     * @return void
     */
    private function _error(int $code, string $msg): void
    {
        http_response_code($code);
        header("HTTP/1.1 $code " . HttpException::HTTP_STATUS_CODES[$code]);

        /*$backtrace = debug_backtrace();
        $msg .= "<br/><h3>Call stack:</h3>";
        foreach ($backtrace as $index => $item) {
            $msg .= "
                <br/><br/><b>File:</b> {$item['file']}
                <br/><b>Line:</b> {$item['line']}
            ";
        }*/
        echo "Error $code: $msg";
        die;
    }
}
