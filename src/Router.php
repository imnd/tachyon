<?php
namespace tachyon;

// exceptions
use Exception,
    Error,
    ReflectionException,
    BadMethodCallException;
use tachyon\exceptions\{
    ErrorException,
    ContainerException,
    DBALException,
    FileNotFoundException,
    HttpException,
    MapperException,
    ModelException,
    NotFoundException,
    ValidationException,
    ViewException
};
// dependencies
use
    app\ServiceContainer,
    tachyon\cache\Output as OutputCache,
    tachyon\components\Message;

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
        // start caching
        $this->cache->start($_SERVER['REQUEST_URI']);

        Request::set('get', $_GET);
        Request::set('post', $_POST);
        Request::set('files', $_FILES);

        // parse the request
        $path = Request::parseUri();
        if (!$this->_parseRoute($path)) {
            $requestArr = explode('/', $path);
            // retrieving the name of the controller and action
            Request::set('controller', 'app\controllers\\' . ucfirst($this->_getNameFromRequest($requestArr)) . 'Controller');
            Request::set('action', $this->_getNameFromRequest($requestArr));
            // parse the array of parameters
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

        // start the controller
        $this->_startController();

        // end caching
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
                throw new HttpException($this->msg->i18n('There is no action "%actionName" in controller "%controllerName".', compact('controllerName', 'actionName')), HttpException::NOT_FOUND);
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
        } catch (
              ReflectionException
            | ErrorException
            | ContainerException
            | DBALException
            | FileNotFoundException
            | HttpException
            | MapperException
            | ModelException
            | NotFoundException
            | ValidationException
            | ViewException
        $e) {
            // Обработчик неправильного запроса. Вывод сообщения об ошибке
            $code = is_a($e, 'tachyon\exceptions\HttpException') ? $e->getCode() : HttpException::INTERNAL_SERVER_ERROR;

            http_response_code($code);
            header("HTTP/1.1 $code " . HttpException::HTTP_STATUS_CODES[$code]);

            echo "Error $code: {$e->getMessage()}\n";

            $trace = $e->getTrace();
            echo "<br/><h3>Stack trace:</h3>\n";
            foreach ($trace as $item) {
                echo "
                    <b>File:</b> {$item['file']}<br/>
                    <b>Line:</b> {$item['line']}<br/>
                    <b>Function:</b> {$item['function']}<br/><br/>
                ";
            }
        }
    }
}
