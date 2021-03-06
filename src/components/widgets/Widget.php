<?php
namespace tachyon\components\widgets;

use tachyon\components\AssetManager,
    tachyon\components\Message,
    tachyon\View;

/**
 * Базовый класс для всех виджетов
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2020 IMND
 */
abstract class Widget
{
    use \tachyon\traits\HasOwner;
    use \tachyon\traits\ClassName;
    use \tachyon\traits\Configurable;

    /**
     * @var \tachyon\components\AssetManager $assetManager
     */
    protected $assetManager;
    /**
     * @var \tachyon\components\Message $msg
     */
    protected $msg;
    /**
     * @var \tachyon\View $view
     */
    protected $view;

    /**
     * id виджета
     * @var $id string
     */
    protected $id;
    /**
     * Из какого контроллера вызван
     * @var $controller \tachyon\Controller
     */
    protected $controller;
    /**
     * Путь файла отображения
     * @var $view string
     */
    protected $viewsPath;
    /**
     * Выводить или возвращать вывод
     * @var $return boolean
     */
    protected $return = false;

    public function __construct(AssetManager $assetManager, Message $msg, View $view)
    {
        $this->assetManager = $assetManager;
        $this->msg = $msg;
        $this->view = $view;
        $this->view->setOwner($this);

        if (is_null($this->viewsPath)) {
            $this->viewsPath = lcfirst(get_called_class());
        }
        if (is_null($this->id)) {
            $this->id = strtolower($this->getClassName()) . '_' . uniqid();
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
     * @param $return boolean показывать или возвращать 
     */
    protected function display($view = '', array $vars = array(), $return = null)
    {
        if (empty($view)) {
            $view = strtolower($this->getClassName());
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
     * 
     * @param string $name
     * @return string
     */
    public function js($name)
    {
        return $this->assetManager->js($name, $this->getAssetsPublicPath() . '/', $this->getAssetsSourcePath());
    }

    /**
     * Выводит стиль виджета
     * 
     * @param string $name
     * @return string
     */
    public function css($name)
    {
        return $this->assetManager->css($name, $this->getAssetsPublicPath() . '/', $this->getAssetsSourcePath());
    }

    # геттеры

    /**
     * Путь до ресурсов
     * 
     * @return string
     */
    public function getAssetsPublicPath()
    {
        return '/widgets/' . strtolower($this->getClassName());
    }

    /**
     * Путь до ресурсов
     * 
     * @return string
     */
    public function getAssetsSourcePath()
    {
        return __DIR__ . '/assets';
    }

    /**
     * getViewPath
     * 
     * @return string
     */
    public function getViewPath()
    {
        $reflection = new \ReflectionClass($this);
        $directory = dirname($reflection->getFileName());
        return $directory . DIRECTORY_SEPARATOR . 'views';
    }
    
    public function setController($controller)
    {
        $this->controller = $controller;
        return $this;
    }

    public function getController()
    {
        return $this->controller;
    }

    public function getId()
    {
        return $this->id;
    }
}
