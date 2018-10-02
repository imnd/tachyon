<?php
namespace tachyon;

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
        $requestUri = $_SERVER['REQUEST_URI'];
        // кеширование
        $this->cache->start($requestUri);
        // разбираем запрос
		$requestArr = explode('/', $requestUri);
        array_shift($requestArr);
		// разбираем имена контроллера
        // TODO: применить parse_url
		$controllerName = $this->_getNameFromRequest($requestArr, 'Index');
        if (strpos($controllerName, '?') !== false) {
            $requestVars['get'] = $this->_parseGet($controllerName);
            $controllerName = substr($controllerName, 0, strpos($controllerName, '?'));
            $actionName = 'index';
        } else {
            // и действия
            $requestStr = $this->_getNameFromRequest($requestArr, 'index');
            $actionName = $this->_getActionName($requestStr);
            // разбираем массив параметров
            $requestVars['get'] = $this->_parseGet($requestStr);
            // разбираем массив параметров
            $requestVars = array_merge_recursive($requestVars, $this->_parseRequest($requestArr));
        }
        // прибавляем переменные $_POST и $_FILES
        $requestVars['post'] = $_POST ?? null;
        $requestVars['files'] = $_FILES ?? null;
		// запускаем соотв. контроллер
		$this->_startController($controllerName, $actionName, $requestVars);
        // кеширование
        $this->cache->end();
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
        $controller->start($actionName, $requestVars);
        // запускаем
        $controller->beforeAction();
        $inlineVars = isset($requestVars['inline']) ? $requestVars['inline'] : null;
        $controller->$actionName($inlineVars);
        $controller->afterAction();
    }
    
    /**
     * обработчик неправильного запроса
     */
    private function _error($code, $error)
    {
        $codes = array(404 => 'Not Found');
        header("HTTP/1.0 $code {$codes[$code]}");
        echo $error;
        die;
    }

    /**
     * _getActionName
     * Извлекает название экшна
     * 
     * @param $requestStr string
     * 
     * @return string
     */
    private function _getActionName($requestStr)
    {
        $requestArr = explode('?', $requestStr);
        return $requestArr[0];
    }

    /**
     * _parseGet
     * Разбор строки запроса и извлечение перем. GET
     * 
     * @param $requestStr string
     * 
     * @return array
     */
    private function _parseGet($requestStr)
    {
        $getVars = array();
        $requestArr = explode('?', $requestStr);
        if (count($requestArr) > 1) {
            $requestStr = $requestArr[1];
            $requestArr = explode('&', $requestStr);
            foreach ($requestArr as $getStr) {
                $getArr = explode('=', $getStr);
                if (count($getArr) > 1) {
                    $getVars[$getArr[0]] = urldecode($getArr[1]);
                }
            }
        }
        return $getVars;
    }

    /**
     * _parseRequest
     * @param $requestArr array
     * 
     * @return array
     */
	private function _parseRequest(array $requestArr)
	{
		$requestVars = array();
        if (count($requestArr)>0) {
            $getVars = array();
            $requestArr = array_chunk($requestArr, 2);
            foreach ($requestArr as $pair) {
                if (isset($pair[1])) {
                    $getVars[$pair[0]] = urldecode($pair[1]);
                } elseif ($pair[0]!=='') {
                    $vars = $this->_parseGet($pair[0]);
                    if (count($vars)) {
                        $getVars = array_merge($getVars, $vars);
                        if (strpos($pair[0], '?')!==false)
                            $requestVars['inline'] = substr($pair[0], 0, strpos($pair[0], '?'));
                    } else
                        $requestVars['inline'] = $pair[0];
                }
            }
            $requestVars['get'] = $getVars;
        }
		return $requestVars;
	}

	/**
	 * _getNameFromRequest
	 * 
	 * @param $requestArr array 
	 * @param $default string
	 * 
	 * @return string
	 */
	private function _getNameFromRequest(array &$requestArr, $default)
	{
		if (count($requestArr)==0)
			return $default;

        if (is_numeric($requestArr[0]))
            return $default;

		$name = array_shift($requestArr);
		if ($name=='')
			return $default;

		return $name;
	}
}
