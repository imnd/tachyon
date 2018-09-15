<?php
namespace tachyon\components\widgets;

/**
 * class Widget
 * Папа для всех виджетов
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
abstract class Widget extends \tachyon\Component
{
    use \tachyon\dic\Config;
    use \tachyon\traits\Configurable;

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
    protected $view;
    /**
     * Выводить или возвращать вывод
     * @var $return boolean
     */
    protected $return = false;

    public function __construct()
    {
        if (is_null($this->view))
            $this->view = lcfirst(get_called_class());

        if (is_null($this->id))
            $this->id = strtolower($this->getClassName()) . '_' . uniqid();
    }

    /**
     * Запуск
     */
    public abstract function run();

    /**
     * Отображает файл представления $view
     * передавая ему параметры $vars в виде массива
     * 
     * @param $view string файл представления
     * @param $vars array переменные представления
     * @param $return boolean показывать или возвращать 
     */
    protected function display($view='', array $vars=array(), $return=null)
    {
        if (empty($view))
            $view = strtolower($this->getClassName());

        if (is_null($return))
            $return = $this->return;

        $vars['widget'] = $this;

        $this->get('View')
            ->setViewsPath($this->getViewPath())
            ->display($view, $vars, $return);
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

    /**
     * Путь до ресурсов
     * 
     * @return string
     */
    public function getAssetsPath()
    {
        return DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'widgets' . DIRECTORY_SEPARATOR . strtolower($this->getClassName()) . DIRECTORY_SEPARATOR;
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
