<?php

namespace tachyon;

# exceptions
use
    Error,
    ErrorException,
    PDOException,
    ReflectionException;

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

# dependencies
use
    tachyon\cache\Output as OutputCache,
    tachyon\components\Message;
use tachyon\Helpers\ClassHelper;

/**
 * @author imndsu@gmail.com
 */
final class FrontController
{
    private OutputCache $cache;
    private Message $msg;
    private Request $request;

    private array $routes;
    private $controller;
    private string $action;
    private $inline;

    public function __construct(
        Config $config,
        OutputCache $cache,
        Message $msg,
        Request $request
    ) {
        $this->cache = $cache;
        $this->msg = $msg;
        $this->request = $request;
        $this->routes = $config->get('routes');
    }

    /**
     * Processing an incoming request and transferring control to the appropriate controller
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

        app()->boot([
            'controller' => $this->controller,
        ]);

        // start the controller
        $this->startController();

        // end caching
        $this->cache->end();
    }

    /**
     * Extract controller and action names from conf route
     */
    private function parseRoute(string $path): bool
    {
        if ($route = $this->routes[$path]) {
            [$this->controller, $this->action] = explode('@', $route);
            return true;
        }

        $pathArr = explode('/', $path);

        foreach ($pathArr as $key => $pathItem) {
            $found = true;
            foreach (array_keys($this->routes) as $route) {
                $routeArr = explode('/', $route);
                if (!$routeItem = $routeArr[$key] ?? null) {
                    $found = false;
                    continue;
                }
                if ($routeItem != $pathItem) {
                    if (substr($routeItem, 0, 1) !== '{' || substr($routeItem, -1, 1) !== '}') {
                        $found = false;
                        continue;
                    }
                }
                $found = true;
            }
            if ($found) {
                [$this->controller, $this->action] = explode('@', $route);
                return true;
            }
        }
        return false;
    }

    /**
     * Retrieving the name of a controller or action
     *
     * @param array $requestArr array of parameters
     */
    private function getNameFromRequest(array &$requestArr): ?string
    {
        if (
               count($requestArr) === 0
            || is_numeric($requestArr[0])
        ) {
            return null;
        }
        return array_shift($requestArr);
    }

    /**
     * Launching the controller
     */
    private function startController(): void
    {
        try {
            if (!$controllerClass = $this->controller) {
                throw new HttpException('Wrong url');
            }
            /** @var Controller $controller */
            $controller = app()->get($controllerClass);
            $controllerName = lcfirst(str_replace('Controller', '', ClassHelper::getClassName($controller)));
            if (empty($actionName = $this->action)) {
                $actionName = $controller->getDefaultAction();
            }
            if (!method_exists($controller, $actionName)) {
                throw new HttpException(t('There is no action "%actionName" in controller "%controllerName".', compact('controllerName', 'actionName')), HttpException::NOT_FOUND);
            }
            $controller
                ->setAction($actionName)
                ->setId($controllerName)
                // launching
                ->start($this->request)
                // initialization
                ->init();

            if (!$controller->beforeAction()) {
                throw new HttpException(t('Method "beforeAction" returned false'), HttpException::BAD_REQUEST);
            }

            ob_start();
            $actionVars = app()->getDependencies($controllerClass, $actionName);
            if (!is_null($inline = $this->inline)) {
                $actionVars[] = $inline;
            }
            $controller->$actionName(...$actionVars);
            $controller->afterAction();

            // everything is ok, we hand over the page
            header('HTTP/1.1 200 OK');
            // clickjacking protection
            header('X-Frame-Options:sameorigin');
            // XSS protection, HTTP Only
            ini_set('session.cookie_httponly', 1);

            echo ob_get_clean();
        } catch (
             ReflectionException
            |Error
            |ErrorException
            |ContainerException
            |DBALException
            |FileNotFoundException
            |HttpException
            |MapperException
            |ModelException
            |NotFoundException
            |PDOException
            |ValidationException
            |ViewException
        $e) {
            // Invalid request handler. Error message output
            $code = $e instanceof HttpException ? $e->getCode() : HttpException::INTERNAL_SERVER_ERROR;

            http_response_code($code);
            header("HTTP/1.1 $code ".HttpException::HTTP_STATUS_CODES[$code]);

            if (file_exists($errorPath = __DIR__ . '/../../../../app/views/error.php')) {
                require $errorPath;
                return;
            }

            require 'errors.php';
        }
    }
}
