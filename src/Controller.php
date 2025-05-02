<?php

namespace tachyon;

use JetBrains\PhpStorm\NoReturn;
use tachyon\components\{
    Cookie, Csrf, Lang, Message,
};
use tachyon\exceptions\HttpException;

/**
 * Parent class for all controllers
 *
 * @author imndsu@gmail.com
 */
class Controller
{
    /** default website layout */
    protected string $layout = 'main';
    /** default controller action */
    protected string $defaultAction = 'index';
    protected string $id;
    protected string $action;

    /** actions for only $_POST requests */
    protected string | array $postActions = [];
    /** actions only for authenticated users */
    protected string | array $protectedActions;

    public function __construct(
        protected readonly Request $request,
        protected Message $msg,
        protected Cookie $cookie,
        protected Lang $lang,
        protected View $view,
        protected Csrf $csrf
    ) {}

    /**
     * initialization
     */
    public function start(): self
    {
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
        $this->view->setController($this);
        // The path to views
        $this->view->setViewsPath("{$this->view->getViewsPath()}/{$this->id}");

        return $this;
    }

    /**
     * Initialization in the client code
     */
    public function init(): void
    {
    }

    /**
     * Hook triggering before the action launch
     */
    public function beforeAction(): bool
    {
        return true;
    }

    /**
     * Hook triggering after the action launch
     */
    public function afterAction(): void
    {
    }

    /**
     * Displays the presentation file, transmitting the parameters as an array
     *
     * @param $view string|null presentation file
     * @param $vars array       representation variables
     * @param $return boolean   show or return
     */
    public function display(string $view = null, array $vars = [], bool $return = false): ?string
    {
        if (empty($view)) {
            $view = lcfirst($this->action);
        }
        return $this->view->display($view, $vars, $return);
    }

    /**
     * Displays the presentation file, transmitting the parameters as an array in a given layout
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
     * Redirects a user to the address: $path
     */
    #[NoReturn]
    public function redirect(string $path): void
    {
        header("Location: $path");
        die;
    }

    # region getters

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
