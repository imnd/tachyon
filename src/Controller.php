<?php

namespace tachyon;

use
    tachyon\exceptions\HttpException,
    tachyon\components\Cookie,
    tachyon\components\Csrf,
    tachyon\components\Lang,
    tachyon\components\Message,
    tachyon\traits\ClassName;

/**
 * Базовый класс для всех контроллеров
 *
 * @author Андрей Сердюк
 * @copyright (c) 2020 IMND
 */
class Controller
{
    use ClassName;

    /**
     * Общий шаблон сайта
     *
     * @var $layout string
     */
    protected string $layout = 'main';
    /**
     * id контроллера
     *
     * @var $id string
     */
    protected $id;
    /**
     * id экшна
     *
     * @var string $action
     */
    protected $action;
    /**
     * @var string $defaultAction
     */
    protected $defaultAction = 'index';

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
    /**
     * Экшны только для $_POST запросов
     *
     * @var mixed
     */
    protected $postActions = [];
    /**
     * Экшны только для аутентифицированных юзеров
     *
     * @var mixed
     */
    protected $protectedActions = [];

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
     * @param Cookie  $cookie
     * @param Lang    $lang
     * @param View    $view
     * @param Csrf    $csrf
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
     * @param array $requestVars
     *
     * @return Controller
     * @throws HttpException
     */
    public function start(array $requestVars = [])
    {
        // переменные запроса
        foreach (['get', 'post', 'files'] as $name) {
            $this->$name = $requestVars[$name] ?? null;
        }
        // проверка на isRequestPost по списку экшнов
        if (in_array($this->action, $this->postActions) && !$this->isRequestPost()) {
            throw new HttpException(
                $this->msg->i18n('Action %action allowed only through post request.', ['action' => $this->action]),
                HttpException::BAD_REQUEST
            );
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
     *
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
     *
     * @return string
     */
    public function display($view = null, array $vars = [], $return = false)
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
     *
     * @return void
     */
    public function view($view = null, array $vars = []): void
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
     *
     * @return void
     */
    public function redirect($path)
    {
        header("Location: $path");
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
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    /**
     * Шорткат
     *
     * @param $queryType string
     *
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
     *
     * @return mixed
     */
    public function getGet(string $index = null)
    {
        if (!is_null($index)) {
            return $this->get[$index] ?? null;
        }
        return $this->get;
    }

    /**
     * Шорткат для $_POST
     *
     * @param $index string
     *
     * @return mixed
     */
    public function getPost(string $index = null)
    {
        if (!is_null($index)) {
            return $this->post[$index] ?? null;
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
     * @param int $id
     *
     * @return string
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @param string $actionName
     *
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
     *
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
