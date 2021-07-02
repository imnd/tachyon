<?php
namespace tachyon;

// exceptions
use
    BadMethodCallException,
    Exception,
    Error,
    ErrorException,
    PDOException,
    ReflectionException
;
use tachyon\exceptions\{
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
     * @var OutputCache $cache
     */
    private OutputCache $cache;
    /**
     * @var Message $msg
     */
    private Message $msg;
    /**
     * @var ServiceContainer $container
     */
    private ServiceContainer $container;
    /**
     * @var Request $request
     */
    private Request $request;
    /**
     * @var array $routes
     */
    private $routes;

    private $controller;
    private $action;
    private $inline;

    /**
     * @param Config           $config
     * @param OutputCache      $cache
     * @param Message          $msg
     * @param ServiceContainer $container
     * @param Request          $request
     */
    public function __construct(
        Config $config,
        OutputCache $cache,
        Message $msg,
        ServiceContainer $container,
        Request $request
    ) {
        $this->cache     = $cache;
        $this->msg       = $msg;
        $this->container = $container;
        $this->request   = $request;
        $this->routes    = $config->get('routes');
    }

    /**
     * Обработка входящего запроса
     * и передача управления соответствующему контроллеру
     */
    public function dispatch(): void
    {
        // start caching
        $this->cache->start($_SERVER['REQUEST_URI']);

        // parse the request
        $path = $this->request->parseUri();
        if (!$this->parseRoute($path)) {
            $requestArr = explode('/', $path);
            // retrieving the name of the controller and action
            $this->controller = 'app\controllers\\' . ucfirst($this->getNameFromRequest($requestArr)) . 'Controller';
            $this->action = $this->getNameFromRequest($requestArr);
            // parse the array of parameters
            if (!empty($requestArr)) {
                $this->inline = array_shift($requestArr);
                $requestArr = array_chunk($requestArr, 2);
                foreach ($requestArr as $pair) {
                    if (isset($pair[1])) {
                        $this->request->add('get', [$pair[0] => urldecode($pair[1])]);
                    }
                }
            }
        }

        $this->container->boot([
            'controller' => $this->controller
        ]);

        // start the controller
        $this->startController();

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
    private function parseRoute(string $path): bool
    {
        if (!isset($this->routes[$path])) {
           return false;
        }
        $pathArr = explode('@', $this->routes[$path]);

        $this->controller = $pathArr[0];
        $this->action = $pathArr[1];

        return true;
    }

    /**
     * Извлечение имени контроллера или экшна
     *
     * @param $requestArr array Массив параметров
     * @return string|null
     */
    private function getNameFromRequest(array &$requestArr): ?string
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
    private function startController(): void
    {
        try {
            if (!$controllerClass = $this->controller) {
                throw new HttpException('Wrong url');
            }
            /** @var Controller $controller */
            $controller = $this->container->get($controllerClass);
            $controllerName = lcfirst(str_replace('Controller', '', $controller->getClassName()));
            if (empty($actionName = $this->action)) {
                $actionName = $controller->getDefaultAction();
            }
            if (!method_exists($controller, $actionName)) {
                throw new HttpException($this->msg->i18n('There is no action "%actionName" in controller "%controllerName".', compact('controllerName', 'actionName')), HttpException::NOT_FOUND);
            }
            $controller
                ->setAction($actionName)
                ->setId($controllerName)
                // запускаем
                ->start($this->request)
                // инициализация
                ->init();

            if (!$controller->beforeAction()) {
                throw new HttpException($this->msg->i18n('Method "beforeAction" returned false'), HttpException::BAD_REQUEST);
            }

            ob_start();
            $actionVars = $this->container->getDependencies($controllerClass, $actionName);
            if (!is_null($inline = $this->inline)) {
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
            | Error
            | ErrorException
            | ContainerException
            | DBALException
            | FileNotFoundException
            | HttpException
            | MapperException
            | ModelException
            | NotFoundException
            | PDOException
            | ValidationException
            | ViewException
        $e) {
            // Обработчик неправильного запроса. Вывод сообщения об ошибке
            $code = $e instanceof HttpException ? $e->getCode() : HttpException::INTERNAL_SERVER_ERROR;

            http_response_code($code);
            header("HTTP/1.1 $code " . HttpException::HTTP_STATUS_CODES[$code]);

            echo "Error $code: {$e->getMessage()}\n";

            $trace = $e->getTrace();

            echo "<br/><h3>Stack trace:</h3>\n<table>";
            foreach ($trace as $item) {
                echo "
                <tr>
                    <td><b>File:</b>&nbsp; {$item['file']}</td>
                    <td><b>Line:</b>&nbsp; {$item['line']}&nbsp;&nbsp;</td>
                    <td>" . (isset($item['class']) ? $item['class'] . ':' : '') . "{$item['function']}()</td>
                </tr>
                ";
            }
            echo "</table>";
        }
    }
}
