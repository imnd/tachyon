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
    use \tachyon\dic\Db;
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
    /**
     * Главное меню
     * @var $mainMenu array
     */
    protected $mainMenu = array();
    /**
     * Меню страницы
     * @var $subMenu array
     */
    protected $subMenu = array();

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
    public function start($actionName, array $requestVars = array())
    {
        // переменные запроса
        $this->_setRequestVar($requestVars, 'get');
        $this->_setRequestVar($requestVars, 'post');
        $this->_setRequestVar($requestVars, 'files');

        // проверка на isRequestPost по списку экшнов
        if (in_array($actionName, $this->postActions) && !$this->isRequestPost()) {
            throw new HttpException($this->msg->i18n('Action %action allowed only through post request.', array('action' => $actionName)), HttpException::BAD_REQUEST);
        }
        $this->action = $actionName;

        $this->id = str_replace('Controller', '', lcfirst($this->getClassName()));

        $this->view->setController($this);
        // путь к отображениям
        $this->view->setViewsPath("{$this->view->getViewsPath()}/{$this->id}");
        // текущий язык сайта
        $this->language = $this->lang->getLanguage();

        // всё в порядке, отдаём страницу
        header('HTTP/1.1 200 OK');
        // защита от кликджекинга
        header('X-Frame-Options:sameorigin');

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
        if (isset($requestVars[$name]))
            $this->$name = $requestVars[$name];
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

        if (empty($view))
            $view = lcfirst($this->action);

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
	}

    /**
     * @return boolean
     */
    public function isRequestPost()
    {
        return $_SERVER['REQUEST_METHOD']==='POST';
    }

    /**
     * Шорткат
     * 
     * @param $queryType string
     * @return string
     * @throws HttpException
     */
    public function getQuery($queryType)
    {
        if (!in_array($queryType, array('get', 'post', 'files'))) {
            throw new HttpException($this->msg->i18n('Invalid request type.', array('action' => $actionName)), HttpException::BAD_REQUEST);
        }
        return $this->$queryType;
    }

    /**
     * @return string
     */
    public function getRoute()
    {
        return htmlspecialchars($_SERVER['REQUEST_URI']);
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getLayout()
    {
        return $this->layout;
    }

    /**
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }
}
