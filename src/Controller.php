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

    # Компоненты

    /**
     * @var Message $msg
     */
    protected Message $msg;
    /**
     * @var Cookie $cookie
     */
    protected Cookie $cookie;
    /**
     * @var Lang $lang
     */
    protected Lang $lang;
    /**
     * @var View $view
     */
    protected View $view;
    /**
     * @var Csrf $csrf
     */
    protected Csrf $csrf;
    /**
     * @var Request
     */
    protected Request $request;

    /**
     * Общий шаблон сайта
     *
     * @var string $layout
     */
    protected string $layout = 'main';
    /**
     * @var string $defaultAction
     */
    protected string $defaultAction = 'index';
    /**
     * id контроллера
     *
     * @var string $id
     */
    protected string $id;
    /**
     * id экшна
     *
     * @var string $action
     */
    protected string $action;

    /**
     * Экшны только для $_POST запросов
     *
     * @var mixed
     */
    protected $postActions;
    /**
     * Экшны только для аутентифицированных юзеров
     *
     * @var mixed
     */
    protected $protectedActions;
    /**
     * @var string
     */
    private string $language;

    /**
     * @param Message $msg
     * @param Cookie  $cookie
     * @param Lang    $lang
     * @param View    $view
     * @param Csrf    $csrf
     * @param Request $request
     */
    public function __construct(
        Message $msg,
        Cookie $cookie,
        Lang $lang,
        View $view,
        Csrf $csrf,
        Request $request
    ) {
        $this->msg = $msg;
        $this->cookie = $cookie;
        $this->lang = $lang;
        $this->view = $view;
        $this->csrf = $csrf;
        $this->request = $request;
    }

    /**
     * Инициализация
     *
     * @return Controller
     * @throws HttpException
     */
    public function start(): self
    {
        // проверка по списку экшнов
        if (in_array($this->action, $this->postActions) && !$this->request->isPost()) {
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
     * Инициализация в клиентском коде
     */
    public function init(): void
    {
    }

    /**
     * Хук, срабатывающий перед запуском экшна
     *
     * @return boolean
     */
    public function beforeAction(): bool
    {
        return true;
    }

    /**
     * Хук, срабатывающий после запуска экшна
     */
    public function afterAction(): void
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
    public function display($view = null, array $vars = [], $return = false): string
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
    public function redirect(string $path): void
    {
        header("Location: $path");
        die;
    }

    # region Getters

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
    public function getDefaultAction(): string
    {
        return $this->defaultAction;
    }

    /**
     * @return string
     */
    public function getLanguage(): ?string
    {
        return $this->language;
    }

    # endregion

    # region Setters

    /**
     * @param string $id
     *
     * @return self
     */
    public function setId(string $id): self
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @param string $actionName
     *
     * @return self
     */
    public function setAction(string $actionName): self
    {
        $this->action = $actionName;
        return $this;
    }

    /**
     * @param string $layout
     *
     * @return self
     */
    public function setLayout(string $layout): self
    {
        $this->layout = $layout;
        return $this;
    }
}
