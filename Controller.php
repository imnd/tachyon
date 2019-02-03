<?php
namespace tachyon;

use tachyon\exceptions\HttpException;

/**
 * Базовый класс для всех контроллеров
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
class Controller extends Component
{
    use \tachyon\traits\Authentication;

    # сеттеры DIC
    use \tachyon\dic\Cookie;
    use \tachyon\dic\Message;
    use \tachyon\dic\Lang;
    use \tachyon\dic\View;
        
    /**
     * Язык сайта
     * @var $language string
     */
    protected $language;
    /**
     * Общий шаблон сайта
     * @var $layout string
     */
    protected $layout = 'main';
    /**
     * id контроллера
     * @var $id string
     */
    protected $id;
    /**
     * id экшна
     * @var $action string
     */
    protected $action;

    # Переменные запроса

    /**
     * @var array $get
     */
    protected $get;
    /**
     * @var array $post
     */
    protected $post;
    /**
     * @var array $files
     */
    protected $files;

    protected $postActions = array();
    /**
     * Экшны только для аутентифицированных юзеров
     * @var array $protectedActions
     */
    protected $protectedActions = array();

    /**
     * Инициализация
     * 
     * @param string $actionName
     * @param array $requestVars
     * @return void
     * @throws HttpException
     */
    public function start(array $requestVars = array())
    {
        // переменные запроса
        foreach (['get', 'post', 'files'] as $key) {
            $this->_setRequestVar($requestVars, $key);
        }
        // проверка на isRequestPost по списку экшнов
        if (in_array($this->action, $this->postActions) && !$this->isRequestPost()) {
            throw new HttpException($this->msg->i18n('Action %action allowed only through post request.', array('action' => $this->action)), HttpException::BAD_REQUEST);
        }

        $this->view->setController($this);
        // путь к отображениям
        $this->view->setViewsPath("{$this->view->getViewsPath()}/{$this->id}");
        // текущий язык сайта
        $this->language = $this->lang->getLanguage();

        // всё в порядке, отдаём страницу
        header('HTTP/1.1 200 OK');
        // защита от кликджекинга
        header('X-Frame-Options:sameorigin');
        // Защита от XSS. HTTP Only
        ini_set('session.cookie_httponly', 1);

        $this->init();
    }

    /**
     * Хук, срабатывающий перед запуском экшна
     * @return boolean
     */
    public function beforeAction()
    {
        if ($this->protectedActions==='*' || in_array($this->action, $this->protectedActions)) {
            $this->checkAccess();
        }
        return true;
    }

    /**
     * Хук, срабатывающий после запуска экшна
     */
    public function afterAction()
    {
    }

    /**
     * Инициализация
     */
    public function init()
    {
    }

    /**
     * Устанавливает переменную запроса $name
     * 
     * @param array $requestVars
     * @param string $name
     */
    private function _setRequestVar($requestVars, $name)
    {
        if (isset($requestVars[$name])) {
            $this->$name = $requestVars[$name];
        }
    }

    /**
     * Отображает файл представления $view
     * передавая ему параметры $vars в виде массива
     * 
     * @param $view string файл представления
     * @param $vars array переменные представления
     * @param $return boolean показывать или возвращать 
     * @return string
     */
    public function display($view=null, array $vars=array(), $return=false)
    {
        if (empty($view)) {
            $view = lcfirst($this->action);
        }
        return $this->view->display($view, $vars, $return);
    }

    /**
     * Отображает файл представления, передавая ему параметры
     * в виде массива в заданном лэйауте
     * 
     * @param $view string
     * @param $vars array 
     * @return string
     */
    public function layout($view=null, array $vars=array())
    {
        $this->view->setLayout($this->layout);

        if (empty($view)) {
            $view = lcfirst($this->action);
        }
        $this->view->layout($view, $vars);
    }

    /**
     * Перенаправляет пользователя на адрес: $path
     * 
     * @param $path string
     * @return void
     */
    public function redirect($path)
    {
        header("Location: $path");
        die;
    }

    /**
     * Вывод сообщения об ошибке
     * @param string $msg
     * @return void
     */
    public function error($code, $msg)
    {
        $codes = array(404 => 'Not Found');
        header("HTTP/1.1 $code {$codes[$code]}");
        $this->layout('/../error', compact('code', 'msg'));
        die;
    }

    # Getters and setters

    /**
     * Страница, с которой редиректились
     * 
     * @return string
     */
    public function getReferer()
    {
        return $_COOKIE['referer'] ?? '/';
    }

    /**
     * Запоминаем страницу, с которой редиректимся
     * 
     * @return void
     */
    public function setReferer()
    {
        setcookie('referer', $_SERVER['REQUEST_URI'], 0, '/');
    }

    /**
     * @return boolean
     */
    public function isRequestPost(): bool
    {
        return $_SERVER['REQUEST_METHOD']==='POST';
    }

    /**
     * Шорткат
     * 
     * @param $queryType string
     * @return array
     */
    public function getQuery(string $queryType = null): array
    {
        if (is_null($queryType)) {
            $queryType = 'get';
        }
        return $this->$queryType;
    }

    /**
     * Шорткат для $_GET
     * 
     * @param $index string
     * @return mixed
     */
    public function getGet(string $index = null)
    {
        if (!is_null($index)) {
            return $this->get[$index] ?? '';
        }
        return $this->get;
    }

    /**
     * Шорткат для $_POST
     * 
     * @param $index string
     * @return mixed
     */
    public function getPost(string $index = null)
    {
        if (!is_null($index)) {
            return $this->post[$index] ?? '';
        }
        return $this->post;
    }

    /**
     * @return string
     */
    public function getRoute(): string
    {
        return htmlspecialchars($_SERVER['REQUEST_URI']);
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * @return string
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function setAction($action)
    {
        $this->action = $action;
        return $this;
    }

    /**
     * @return void
     */
    public function setLayout(string $layout)
    {
        $this->layout = $layout;
        return $this;
    }

    /**
     * @return string
     */
    public function getLayout(): string
    {
        return $this->layout;
    }

    /**
     * @return string
     */
    public function getLanguage()//: ?string
    {
        return $this->language;
    }
}
