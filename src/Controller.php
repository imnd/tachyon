<?php
namespace tachyon;

use
    tachyon\exceptions\HttpException,
    tachyon\components\Cookie,
    tachyon\components\Csrf,
    tachyon\components\Lang,
    tachyon\components\Message,
    tachyon\traits\ClassName
;

/**
 * Базовый класс для всех контроллеров
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
class Controller
{
    use ClassName;

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
     * @var string $action
     */
    protected $action;
    /**
     * @var string $defaultAction
     */
    protected $defaultAction = 'index';
    /**
     * Экшны только для $_POST запросов
     * @var mixed
     */
    protected $postActions = array();
    /**
     * Экшны только для аутентифицированных юзеров
     * @var mixed
     */
    protected $protectedActions = array();

    # Компоненты

    /**
     * @var Message $msg
     */
    protected $msg;
    /**
     * @var Cookie $cookie
     */
    protected $cookie;
    /**
     * @var Lang $lang
     */
    protected $lang;
    /**
     * @var View $view
     */
    protected $view;
    /**
     * @var Csrf $csrf
     */
    protected $csrf;

    /**
     * @param Message $msg
     * @param Cookie $cookie
     * @param Lang $lang
     * @param View $view
     * @param Csrf $csrf
     *
     * @return void
     */
    public function __construct(Message $msg, Cookie $cookie, Lang $lang, View $view, Csrf $csrf)
    {
        $this->msg = $msg;
        $this->cookie = $cookie;
        $this->lang = $lang;
        $this->view = $view;
        $this->csrf = $csrf;
    }

    /**
     * Инициализация
     *
     * @return Controller
     * @throws HttpException
     */
    public function start()
    {
        // проверка на isRequestPost по списку экшнов
        if (in_array($this->action, $this->postActions) && !Request::isPost()) {
            throw new HttpException($this->msg->i18n('Action %action allowed only through post request.', ['action' => $this->action]), HttpException::BAD_REQUEST);
        }
        // проверка CSRF токена
        if (!$this->csrf->isTokenValid()) {
            throw new HttpException($this->msg->i18n('Wrong CSRF token.', HttpException::BAD_REQUEST));
        }

        $this->view->setController($this);
        // путь к отображениям
        $this->view->setViewsPath("{$this->view->getViewsPath()}/{$this->id}");
        // текущий язык сайта
        $this->language = $this->lang->getLanguage();

        return $this;
    }

    /**
     * Инициализация
     */
    public function init()
    {
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
    public function view($view=null, array $vars=array())
    {
        $this->view->setLayout($this->layout);

        if (empty($view)) {
            $view = lcfirst($this->action);
        }
        $this->view->view($view, $vars);
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

    # Getters and setters

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
     * @param int $id
     * @return string
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @param string $actionName
     * @return string
     */
    public function setAction(string $actionName)
    {
        $this->action = $actionName;
        return $this;
    }

    /**
     * @return string
     */
    public function getDefaultAction()
    {
        return $this->defaultAction;
    }

    /**
     * @param string $layout
     * @return Controller
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
    public function getLanguage(): ?string
    {
        return $this->language;
    }
}
