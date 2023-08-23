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

    protected Message $msg;
    protected Cookie $cookie;
    protected Lang $lang;
    protected View $view;
    protected Csrf $csrf;
    protected Request $request;

    /**
     * Общий шаблон сайта
     */
    protected string $layout = 'main';
    protected string $defaultAction = 'index';
    /**
     * id контроллера
     */
    protected string $id;
    /**
     * id экшна
     */
    protected string $action;

    /**
     * Экшны только для $_POST запросов
     */
    protected $postActions = [];
    /**
     * Экшны только для аутентифицированных юзеров
     */
    protected $protectedActions;
    private string $language;

    public function __construct(
        Message $msg,
        Cookie $cookie,
        Lang $lang,
        View $view,
        Csrf $csrf
    ) {
        $this->msg = $msg;
        $this->cookie = $cookie;
        $this->lang = $lang;
        $this->view = $view;
        $this->csrf = $csrf;
    }

    /**
     * Инициализация
     */
    public function start(Request $request): self
    {
        $this->request = $request;
        // проверка по списку экшнов
        if (
           (
                  $this->postActions === '*'
               || in_array($this->action, $this->postActions)
           )
        && !$this->request->isPost()) {
            throw new HttpException(
                $this->msg->i18n('Action %action allowed only through post request.', ['action' => $this->action]),
                HttpException::BAD_REQUEST
            );
        }
        // проверка CSRF токена
        if (!$this->csrf->isTokenValid()) {
            throw new HttpException($this->msg->i18n('Wrong CSRF token.', HttpException::BAD_REQUEST));
        }
        $this->view->setRequest($request);
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
     * @param $view string|null файл представления
     * @param $vars array переменные представления
     * @param $return boolean показывать или возвращать
     *
     * @return string
     */
    public function display(string $view = null, array $vars = [], bool $return = false): ?string
    {
        if (empty($view)) {
            $view = lcfirst($this->action);
        }
        return $this->view->display($view, $vars, $return);
    }

    /**
     * Отображает файл представления, передавая ему параметры
     * в виде массива в заданном лэйауте
     */
    public function view(string $view = '', array $vars = []): void
    {
        if ($this->layout) {
            $this->view->setLayout($this->layout);
        }
        if (empty($view)) {
            $view = lcfirst($this->action);
        }
        $this->view->view($view, $vars);
    }

    /**
     * Перенаправляет пользователя на адрес: $path
     */
    public function redirect(string $path): void
    {
        header("Location: $path");
        die;
    }

    # region Getters

    public function getId(): string
    {
        return $this->id;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getDefaultAction(): string
    {
        return $this->defaultAction;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    # endregion

    # region Setters

    public function setId(string $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function setAction(string $actionName): self
    {
        $this->action = $actionName;
        return $this;
    }

    public function setLayout(string $layout): self
    {
        $this->layout = $layout;
        return $this;
    }

    # endregion
}
