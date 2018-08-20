<?php
namespace tachyon;

/**
 * Базовый класс для всех контроллеров
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
class Controller extends Component
{
    # геттеры/сеттеры DIC
    use \tachyon\dic\Config;
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
     * Инициализация
     * @return void
     */
    public function start($actionName, array $requestVars = array())
    {
        // переменные запроса
        $this->_setRequestVar($requestVars, 'get');
        $this->_setRequestVar($requestVars, 'post');
        $this->_setRequestVar($requestVars, 'files');

        // проверка на isRequestPost по списку экшнов
        if (in_array($actionName, $this->postActions) && !$this->isRequestPost()) {
            throw new \Exception("Action $actionName allowed only through post request");
        }
        $this->action = $actionName;

        $this->id = str_replace('Controller', '', lcfirst($this->getClassName()));

        $this->view->setController($this);
        // путь к отображениям
        $this->view->setViewsPath("{$this->view->getViewsPath()}/{$this->id}");
        // текущий язык сайта
        $this->language = $this->getLang()->getLanguage();

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
     */
    public function display($view=null, array $vars=array(), $return=false)
	{
		if (empty($view))
            $view = lcfirst($this->action);
        
        return $this->view->display($view, $vars, $return);
	}

	/**
     * Отображает файл представления 
     * передавая ему параметря в виде массива
     * в заданном лэйауте
     * 
     * @param $view string
     * @param $vars array 
     * @return
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
     * @return
     */
    public function redirect($path)
	{
		header("Location: $path");
	}

    /**
     * Find out is request post
     * 
     * @return boolean
     */
    public function isRequestPost()
    {
        return $_SERVER['REQUEST_METHOD']==='POST';
    }

    public function getQuery($queryType)
    {
        $queryTypes = array('get', 'post', 'files');
        if (!in_array($queryType, $queryTypes))
            throw new \Exception('Недопустимый тип запроса.');

        return $this->$queryType;
    }

    public function getRoute()
    {
        return htmlspecialchars($_SERVER['REQUEST_URI']);
    }

    # геттеры

    public function getId()
    {
        return $this->id;
    }

    public function getLayout()
    {
        return $this->layout;
    }

    public function getLanguage()
    {
        return $this->language;
    }
}
