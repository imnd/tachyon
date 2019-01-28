<?php
namespace tachyon;

use tachyon\exceptions\HttpException;

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

	/**
	 * Обработка входящего запроса
	 * и передача управления соотв. контроллеру
	 */
	public function dispatch()
	{
        // Защита от XSS. HTTP Only
        ini_set('session.cookie_httponly', 1);

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
        // Извлекаем имя контроллера
		$controllerName = $this->_getNameFromRequest($requestArr, 'Index');
        // Извлекаем имя экшна
        $actionName = $this->_getNameFromRequest($requestArr, 'index');
        // разбираем массив параметров
        $requestVars = array_merge_recursive($requestVars, $this->_parseRequest($requestArr));
        foreach (['get', 'post', 'inline'] as $key) {
            $this->_filterVars($requestVars[$key]);
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
        if (count($requestArr)===0) {
            return $default;
        }
        if (is_numeric($requestArr[0])) {
            return $default;
        }
        $name = array_shift($requestArr);
        if ($name==='') {
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
    private function _filterVars(&$vars)
    {
        if (empty($vars)) {
            return;
        }
        if (is_string($vars)) {
            $vars = array($vars);
        }
        foreach ($vars as &$value) {
            $value = urlencode($value);
            //$value = str_replace(['|', '&', ';', '$', '%', '@', "\\'", "'", '\\"', '"', '\\', '<', '>', '(', ')', '+', ',', "\t", "\n", "\r"], '', $value);
        }
    }

    /**
     * запускаем контроллер
     */
    private function _startController($controllerName, $actionName, $requestVars)
    {
        $controllerName = ucfirst($controllerName) . 'Controller';
        $error = 'Путь не найден';
        if (!file_exists($this->config->getOption('base_path') . "/../app/controllers/$controllerName.php")) {
            $this->_error(404, $error);
        }
        $controller = $this->get($controllerName);
        if (!method_exists($controller, $actionName)) {
            $this->_error(404, $error);
        }
        // инициализация
        try {
            $controller->start($actionName, $requestVars);
            // запускаем
            $controller->beforeAction();
            $inlineVars = isset($requestVars['inline']) ? $requestVars['inline'] : null;
            $controller->$actionName($inlineVars);
            $controller->afterAction();
        } catch (HttpException $e) {
            $this->_error($e->getCode(), $e->getMessage());
        }
    }
    
    /**
     * обработчик неправильного запроса
     */
    private function _error($code, $error)
    {
        http_response_code($code);
        echo $error;
        die;
    }
}
