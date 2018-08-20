<?php
namespace tachyon;

/**
 * class View
 * Компонент отображения
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
class View extends Component
{
    # геттеры/сеттеры DIC
    use \tachyon\dic\Config;
    use \tachyon\dic\Message;
    use \tachyon\dic\Html;

    protected $controller;
    protected $rootViewsPath;
    protected $viewsPath;
    protected $layout;
    protected $pageTitle;

    /**
     * Инициализация
     * @return void
     */
    public function __construct()
    {
        // путь к отображениям
        $this->rootViewsPath = $this->viewsPath = $this->getConfig()->getOption('base_path') . '/../app/views';
    }

	/**
     * Отображает файл представления $view
     * передавая ему параметры $vars в виде массива
     * 
     * @param $view string файл представления
     * @param $vars array переменные представления
     * @param $return boolean показывать или возвращать 
     */
    public function display($viewName, array $vars=array(), $return=false)
	{
        $contents = $this->_view("{$this->viewsPath}/$viewName.php", $vars);
        if ($return)
            return $contents;

        echo $contents;
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
    public function layout($viewPath, array $vars=array())
	{
        $view = $this->display($viewPath, $vars, true);
        $layoutPath = $this->getLayoutPath();
        $head = $this->_view("$layoutPath/head.php", $vars);
        $foot = $this->_view("$layoutPath/foot.php", $vars);

        echo $head, $view, $foot;
	}

    private function _view($filePath, array $vars=array())
    {
        if (!file_exists($filePath)) {
            $error = "{$this->msg->i18n('No view file found')}: \"$filePath\"\n";
            require __DIR__ . '/error.php';
            die;
        }
        extract($vars);
        ob_start();
        require($filePath);
        $contents = ob_get_contents();
        ob_end_clean();
        return $contents;
    }

    /**
     * Подключение js-кода
     * 
     * @param string $source текст кода либо путь
     * @param string $mode инлайн либо внешний файл
     * @return 
     */
    public function jsCode($source, $mode='inner')
    {
        $script = "<script";
        if ($mode==='inner')
            $script .= ">
                $source
            ";
        else
            $script .= " src=\"$source\">";

        return "$script</script>";
    }

    /**
     * widget
     * Запуск виджета на странице
     * 
     * @param $params array 
     */
    public function widget($params)
    {
        $class = $params['class'];
        unset($params['class']);
        $controller = is_null($this->controller) ? $params['controller'] : $this->controller;
        $widget = \tachyon\dic\Container::getInstanceOf($class);
        $widget->setVariables($params);
        $widget->setController($controller);
        return $widget->run();
    }

    /**
     * Выводит переведенные сообщения
     * 
     * @param string 
     * @return string
     */
    public function i18n($msg)
    {
        return $this->msg->i18n($msg);
    }

    # Геттеры и сеттеры

    /**
     * Путь к лэйауту
     * @return string
     */
    private function getLayoutPath()
    {
        return "{$this->rootViewsPath}/layouts/{$this->layout}";
    }

    /**
     * @param string $path
     * @return void
     */
    public function setViewsPath($path)
    {
        $this->viewsPath = $path;
    }

    /**
     * @return string
     */
    public function getViewsPath()
    {
        return $this->viewsPath;
    }

    /**
     * @return string
     */
    public function getLayout()
    {
        return $this->layout;
    }

    /**
     * @param string $layout
     * @return void
     */
    public function setLayout($layout)
    {
        $this->layout = $layout;
    }

    /**
     * @return string
     */
    public function getPageTitle()
    {
        return $this->pageTitle;
    }

    /**
     * @param string $pageTitle
     * @return void
     */
    public function setPageTitle($pageTitle)
    {
        $this->pageTitle = $pageTitle;
    }

    /**
     * @param Controller $controller
     * @return void
     */
    public function setController(Controller $controller)
    {
        $this->controller = $controller;
    }

    /**
     * @return Controller
     */
    public function getController()
    {
        return $this->controller;
    }
}
