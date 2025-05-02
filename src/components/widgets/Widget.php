<?php
namespace tachyon\components\widgets;

use tachyon\components\AssetManager,
    tachyon\components\Message,
    tachyon\View;
use tachyon\Controller;
use tachyon\helpers\ClassHelper;
use tachyon\interfaces\HasOwnerInterface;
use tachyon\traits\HasOwner;
use tachyon\traits\HasProperties;

/**
 * Базовый класс для всех виджетов
 * 
 * @author imndsu@gmail.com
 */
abstract class Widget implements HasOwnerInterface
{
    use HasOwner;
    use HasProperties;

    /**
     * id виджета
     */
    protected string $id = '';
    /**
     * Из какого контроллера вызван
     */
    protected Controller $controller;
    /**
     * Путь файла отображения
     */
    protected string $viewsPath = '';
    /**
     * Выводить или возвращать вывод
     */
    protected bool $return = false;

    public function __construct(
        protected AssetManager $assetManager,
        protected Message $msg,
        protected View $view
    ) {
        $this->view->setOwner($this);

        if (is_null($this->viewsPath)) {
            $this->viewsPath = lcfirst(get_called_class());
        }
        if (is_null($this->id)) {
            $this->id = strtolower(ClassHelper::getClassName($this)) . '_' . uniqid();
        }
    }

    /**
     * Запуск
     */
    abstract public function run();

    /**
     * Отображает файл представления $view
     * передавая ему параметры $vars в виде массива
     * 
     * @param $view string файл представления
     * @param $vars array переменные представления
     * @param $return boolean|null показывать или возвращать
     */
    protected function display(string $view = '', array $vars = [], bool $return = null): ?string
    {
        if (empty($view)) {
            $view = strtolower(ClassHelper::getClassName($this));
        }
        if (is_null($return)) {
            $return = $this->return;
        }
        $vars['widget'] = $this;

        return $this->view
            ->setViewsPath($this->getViewPath())
            ->display($view, $vars, $return);
    }

    /**
     * Выводит скрипт виджета
     */
    public function js(string $name): string
    {
        return $this->assetManager->js(
            $name,
            "{$this->getAssetsPublicPath()}/{$this->getAssetsSourcePath()}",
        );
    }

    /**
     * Выводит стиль виджета
     */
    public function css(string $name): string
    {
        return $this->assetManager->css($name, $this->getAssetsPublicPath() . '/', $this->getAssetsSourcePath());
    }

    # region getters

    /**
     * The public path to assets
     */
    public function getAssetsPublicPath(): string
    {
        return '/widgets/' . strtolower(ClassHelper::getClassName($this));
    }

    /**
     * The path to source assets
     */
    public function getAssetsSourcePath(): string
    {
        return __DIR__ . '/assets';
    }

    public function getViewPath(): string
    {
        $reflection = new \ReflectionClass($this);
        $directory = dirname($reflection->getFileName());
        return $directory . DIRECTORY_SEPARATOR . 'views';
    }
    
    public function setController(Controller $controller): static
    {
        $this->controller = $controller;
        return $this;
    }

    public function getController(): Controller
    {
        return $this->controller;
    }

    public function getId(): string
    {
        return $this->id;
    }

    # endregion
}
