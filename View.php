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
    # сеттеры DIC
    use \tachyon\dic\AssetManager;
    use \tachyon\dic\Message;
    use \tachyon\dic\Html;

    protected $controller;
    /**
     * путь к отображениям
     * @var string $rootViewsPath
     */
    protected $rootViewsPath;
    /**
     * путь к отображениям
     * @var string $rootViewsPath
     */
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
        $this->rootViewsPath = $this->viewsPath = $this->get('config')->getOption('base_path') . '/../app/views';
    }

	/**
     * Отображает файл представления $view
     * передавая ему параметры $vars в виде массива
     * 
     * @param $view string файл представления
     * @param $vars array переменные представления
     * @param $return boolean показывать или возвращать 
     * @return mixed
     */
    public function display($viewName, array $vars=array(), $return=false)
	{
        $contents = $this->_view("{$this->viewsPath}/$viewName.php", $vars);
        if ($return) {
            return $contents;
        }
        echo $contents;
	}

	/**
     * Отображает файл представления 
     * передавая ему параметря в виде массива
     * в заданном лэйауте
     * 
     * @param $view string
     * @param $vars array 
     * @return void
     */
    public function layout($viewPath, array $vars=array())
	{
        $view = $this->display($viewPath, $vars, true);
        $layoutPath = "{$this->rootViewsPath}/layouts/{$this->layout}";
        $head = $this->_view("$layoutPath/head.php", $vars);
        $foot = $this->_view("$layoutPath/foot.php", $vars);

        echo $head, $view, $foot;
	}

    private function _view($filePath, array $vars=array())
    {
        if (!file_exists($filePath)) {
            $error = "{$this->msg->i18n('No view file found')}: \"$filePath\"\n";
            echo "<div class='error'>$error</div>";
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
        $widget = $this->get($class);
        $widget->setVariables($params);
        $controller = is_null($this->controller) ? $params['controller'] : $this->controller;
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
     * @param string $path
     * @return View
     */
    public function setViewsPath($path): View
    {
        $this->viewsPath = $path;
        return $this;
    }

    /**
     * @return string
     */
    public function getViewsPath(): string
    {
        return $this->viewsPath;
    }

    /**
     * @return string
     */
    public function getLayout(): string
    {
        return $this->layout;
    }

    /**
     * @param string $layout
     * @return View
     */
    public function setLayout($layout): View
    {
        $this->layout = $layout;
        return $this;
    }

    /**
     * @return string
     */
    public function getPageTitle(): string
    {
        return $this->pageTitle;
    }

    /**
     * @param string $pageTitle
     * @return View
     */
    public function setPageTitle($pageTitle): View
    {
        $this->pageTitle = $pageTitle;
        return $this;
    }

    /**
     * @param Controller $controller
     * @return View
     */
    public function setController(Controller $controller): View
    {
        $this->controller = $controller;
        return $this;
    }

    /**
     * @return Controller
     */
    public function getController(): Controller
    {
        return $this->controller;
    }

    /**
     * Экранирование вывода
     * 
     * @param string $text
     * @return string
     */
    public function escape($text)
    {
        return htmlspecialchars( $text );
    }
}
