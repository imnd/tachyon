<?php

namespace tachyon;

use JetBrains\PhpStorm\NoReturn;
use tachyon\components\Cookie;
use tachyon\components\Csrf;
use tachyon\components\Lang;
use tachyon\components\Message;
use tachyon\exceptions\HttpException;

/**
 * Base class for all controllers
 *
 * @author imndsu@gmail.com
 */
class Controller
{
    protected Request $request;

    /** Common website template */
    protected string $layout = 'main';
    protected string $defaultAction = 'index';
    /** controller id */
    protected string $id;
    /** controller action */
    protected string $action;

    /** actions for only $_POST requests */
    protected $postActions = [];
    /** actions only for authenticated users */
    protected $protectedActions;

    public function __construct(
        protected Message $msg,
        protected Cookie $cookie,
        protected Lang $lang,
        protected View $view,
        protected Csrf $csrf
    ) {}

    /**
     * initialization
     */
    public function start(Request $request): self
    {
        $this->request = $request;
        // check by the actions list
        if (
                (
                       $this->postActions === '*'
                    || in_array($this->action, $this->postActions)
                )
            && !$this->request->isPost()) {
            throw new HttpException(
                t('Action %action allowed only through post request.', ['action' => $this->action]),
                HttpException::BAD_REQUEST
            );
        }
        // CSRF token check
        if (!$this->csrf->isTokenValid()) {
            throw new HttpException(t('Wrong CSRF token.'), HttpException::BAD_REQUEST);
        }
        $this->view->setRequest($request);
        $this->view->setController($this);
        // путь к отображениям
        $this->view->setViewsPath("{$this->view->getViewsPath()}/{$this->id}");

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
    #[NoReturn]
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
