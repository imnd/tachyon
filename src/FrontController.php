<?php

namespace tachyon;

# exceptions
use Error;
use ErrorException;
use Exception;
use PDOException;
use ReflectionException;
use tachyon\cache\Output as OutputCache;
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
use tachyon\helpers\ClassHelper;

# dependencies

/**
 * @author imndsu@gmail.com
 */
final class FrontController
{
    private array $routes = [];
    private string $controller = '';
    private string $action = '';
    private array $inline = [];

    public function __construct(
        Config $config,
        private readonly OutputCache $cache,
        private readonly Request $request
    ) {
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
        if (!$this->parseRoute($path = $this->request->parseUri())) {
            $requestArr = explode('/', $path);
            // retrieving the name of the controller and action
            $this->controller = 'app\controllers\\' . ucfirst($this->getNameFromRequest($requestArr)) . 'Controller';
            $this->action = $this->getNameFromRequest($requestArr) ?? 'index';
            // parse the array of parameters
            if (!empty($requestArr)) {
                $this->inline[] = array_shift($requestArr);
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
        if ($route = $this->routes[$path] ?? null) {
            [$this->controller, $this->action] = explode('@', $route);
            return true;
        }

        $pathParts = explode('/', $path);

        foreach ($this->routes as $route => $controllerAndAction) {
            $found = true;
            $routeParts = explode('/', $route);
            if (count($pathParts) !== count($routeParts)) {
                continue;
            }
            foreach ($pathParts as $i => $pathPart) {
                if (!$routePart = $routeParts[$i] ?? null) {
                    $found = false;
                    break;
                }
                if ($routePart !== $pathPart) {
                    if (!str_starts_with($routePart, '{') || !str_ends_with($routePart, '}')) {
                        $found = false;
                        break;
                    }
                    $paramName = str_replace(['{', '}'], '', $routePart);
                    $this->inline[$paramName] = $pathPart;
                }
            }
            if ($found) {
                [$this->controller, $this->action] = explode('@', $controllerAndAction);
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
                throw new HttpException('Wrong url', HttpException::NOT_FOUND);
            }
            /** @var Controller $controller */
            $controller = app()->get($controllerClass);
            $controllerName = lcfirst(str_replace('Controller', '', ClassHelper::getClassName($controller)));
            if (empty($actionName = $this->action)) {
                $actionName = $controller->getDefaultAction();
            }
            if (!method_exists($controller, $actionName)) {
                throw new HttpException('Wrong url', HttpException::NOT_FOUND);
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

            $actionVars = [
                ...app()->getDependencies($controllerClass, $actionName),
                ...$this->inline
            ];
            $controller->$actionName(...$actionVars);
            $controller->afterAction();

            // everything is ok, we hand over the page
            $this->sendHeaders(200);
            // clickjacking protection
            header('X-Frame-Options:sameorigin');
            // XSS protection, HTTP Only
            ini_set('session.cookie_httponly', 1);

            echo ob_get_clean();
        } catch (HttpException $e) {
            $this->sendHeaders($e->getCode());
            $this->showErrorPage($e, 404);
        } catch (
            ReflectionException
          | Error
          | ErrorException
          | ContainerException
          | DBALException
          | FileNotFoundException
          | MapperException
          | ModelException
          | NotFoundException
          | PDOException
          | ValidationException
          | ViewException
        $e) {
            // Invalid request handler. Error message output
            $this->sendHeaders(HttpException::INTERNAL_SERVER_ERROR);
            $this->showErrorPage($e, 500);
        }
    }

    private function sendHeaders(int $code): void
    {
        http_response_code($code);
        header("HTTP/1.1 $code " . HttpException::HTTP_STATUS_CODES[$code]);
    }

    private function showErrorPage(Exception $e, string $fileName = 'error'): void
    {
        if (file_exists($errorPath = __DIR__ . "/../../../../app/views/$fileName.php")) {
            view()
                ->setPageTitle('Error')
                ->view($fileName, [
                    'message' => $e->getMessage(),
                ]);

            return;
        }

        require 'errors.php';
    }
}
