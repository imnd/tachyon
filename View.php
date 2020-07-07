<?php

namespace tachyon;

use tachyon\dic\Container;
use tachyon\components\{
    AssetManager,
    Message,
    html\Html,
    Flash
};
use tachyon\traits\{
    HasOwner,
    HasProperties
};

/**
 * class View
 * Компонент отображения
 *
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
class View
{
    use HasOwner, HasProperties;

    /**
     * Контроллер, вызывающий вью
     *
     * @var Controller $controller
     */
    protected $controller;
    /**
     * Путь к отображениям
     *
     * @var string $rootViewsPath
     */
    protected $viewsPath;
    /**
     * Путь к отображениям
     *
     * @var string $rootViewsPath
     */
    protected $appViewsPath;
    /**
     * Путь к папке лэйаута
     *
     * @var string $layoutPath
     */
    protected $layoutPath;
    /**
     * Имя лэйаута
     *
     * @var string $layoutPath
     */
    protected $layout;
    /**
     * Заголовок страницы
     *
     * @var string $pageTitle
     */
    protected $pageTitle;

    /**
     * @var Config $config
     */
    protected $config;
    /**
     * @var AssetManager $assetManager
     */
    protected $assetManager;
    /**
     * @var Message $msg
     */
    protected $msg;
    /**
     * Компонент построителя html-кода
     *
     * @var Html $html
     */
    protected $html;
    /**
     * @var Flash
     */
    protected $flash;

    /**
     * Сохраняем переменные между отрисовкой наследуемых лэйаутов
     */
    protected $layoutVars = [];

    /**
     * @param Config       $config
     * @param AssetManager $assetManager
     * @param Message      $msg
     * @param Html         $html
     * @param Flash        $flash
     */
    public function __construct(
        Config $config,
        AssetManager $assetManager,
        Message $msg,
        Html $html,
        Flash $flash
    ) {
        $this->config = $config;
        $this->appViewsPath = $this->viewsPath = $this->config->get('base_path') . '/../app/views';
        $this->assetManager = $assetManager;
        $this->msg = $msg;
        $this->html = $html;
        $this->flash = $flash;
    }

    /**
     * Отображает файл представления $view
     * передавая ему параметры $vars в виде массива
     *
     * @param string  $viewName
     * @param array   $vars переменные представления
     * @param boolean $return показывать или возвращать
     *
     * @return mixed
     */
    public function display(
        string $viewName,
        array $vars = [],
        bool $return = false
    ) {
        $contents = $this->_view("{$this->viewsPath}/$viewName", $vars);
        if ($return) {
            return $contents;
        }
        echo $contents;
    }

    /**
     * Отображает файл представления, передавая ему параметры
     * в виде массива в заданном лэйауте
     *
     * @param string $viewsPath
     * @param array  $vars
     *
     * @return void
     */
    public function view(string $viewsPath, array $vars = []): void
    {
        $this->layoutPath = "{$this->appViewsPath}/layouts";
        echo $this->_displayLayout($this->display($viewsPath, $vars, true), $vars);
    }

    private function _displayLayout(string $viewContents, array $vars)
    {
        $layoutHtml = $this->_view("{$this->layoutPath}/{$this->layout}", $vars);
        if (false !== $extendsPos = strpos($layoutHtml, '@extends')) {
            $start = $extendsPos + strlen('@extends') + 2;
            $end = strpos($layoutHtml, "'", $start);
            // устанавливаем родительский лэйаут
            $this->layout = substr($layoutHtml, $start, $end - $start);
            // убираем тег '@extends'
            $layoutHtml = substr($layoutHtml, $end + 2);
            // отрисовываем родительский лэйаут
            $layoutHtml = $this->_displayLayout($layoutHtml, $vars);
        }
        $layoutHtml = $this->_replaceTag($layoutHtml, $viewContents, '@contents');
        $this->assetManager->finalize($layoutHtml);
        return $layoutHtml;
    }

    /**
     * Заменяет текст тэга $tag в тексте $textToReplace на $text
     *
     * @param string $textToReplace
     * @param string $text
     * @param string $tag
     *
     * @return string
     */
    private function _replaceTag(string $textToReplace, string $text, string $tag): string
    {
        $tagPos = strpos($textToReplace, $tag);
        return substr($textToReplace, 0, $tagPos) . $text . substr($textToReplace, $tagPos + strlen($tag) + 1);
    }

    private function _view($filePath, array $vars = [])
    {
        $filePath = "$filePath.php";
        if (!file_exists($filePath)) {
            $error = "{$this->msg->i18n('No view file found')}: \"$filePath\"\n";
            echo "<div class='error'>$error</div>";
            die;
        }
        $buffer = file_get_contents($filePath);
        if (false !== strpos($buffer, '{{')) {
            $tempViewFilePath = "{$_SERVER['DOCUMENT_ROOT']}/../runtime/templates/" . md5($filePath) . '.php';
            if (
                // в debug mode скомпиленные шаблоны переписываются всегда
                $this->config->get('mode') !== 'production'
                || !file_exists($tempViewFilePath)
            ) {
                while (false !== $echoPos = strpos($buffer, '{{')) {
                    $start = $echoPos + 2;
                    $end = strpos($buffer, '}}', $start);
                    $text = substr($buffer, $start, $end - $start);
                    $buffer = substr($buffer, 0, $echoPos) . '<?=trim($this->escape(' . $text . '))?>' . substr($buffer,
                            $end + 2);
                }
                file_put_contents($tempViewFilePath, $buffer);
            }
            $filePath = $tempViewFilePath;
        }
        ob_start();
        extract($vars);
        if ('_displayLayout' === debug_backtrace()[1]['function']) {
            // отрисовка лэйаута
            require($filePath);
            $layoutVars = get_defined_vars();
            foreach ($layoutVars as $name => $var) {
                if (isset($vars[$name])) {
                    unset($layoutVars[$name]);
                }
            }
            foreach (['filePath', 'buffer', 'vars', 'this'] as $varName) {
                unset($layoutVars[$varName]);
            }
            // переписываем значения переменных в дочерних лэйаутах
            foreach ($this->layoutVars as $name => $var) {
                if (isset($this->layoutVars[$name])) {
                    unset($layoutVars[$name]);
                }
            }
            // собираем все переменные объявленные в лэйаутах
            $this->layoutVars = array_merge($this->layoutVars, $layoutVars);
        } else {
            // отрисовка вью
            extract($this->layoutVars);
            require($filePath);
        }
        $contents = ob_get_contents();
        ob_end_clean();
        return $contents;
    }

    /**
     * Подключение js-кода
     *
     * @param string $source текст кода либо путь
     * @param string $mode инлайн либо внешний файл
     *
     * @return string
     */
    public function jsCode(string $source, string $mode = 'inner'): string
    {
        $script = '<script';
        if ($mode === 'inner') {
            $script .= ">
                $source
            ";
        } else {
            $script .= " src=\"$source\">";
        }
        return "$script</script>";
    }

    /**
     * widget
     * Запуск виджета на странице
     *
     * @param array $params
     *
     * @return mixed
     */
    public function widget(array $params)
    {
        $class = $params['class'];
        unset($params['class']);
        $widget = (new Container)->get($class);
        $widget->setVariables($params);
        $widget->setOwner($this);
        $controller = $this->controller ?: $params['controller'];
        $widget->setController($controller);
        return $widget->run();
    }

    /**
     * Выводит переведенные сообщения
     *
     * @param string
     *
     * @return string
     */
    public function i18n(string $msg): string
    {
        return $this->msg->i18n($msg);
    }

    /**
     * Экранирование вывода
     *
     * @param string $text
     *
     * @return string
     */
    public function escape(string $text = null): string
    {
        return htmlspecialchars($text ?: '');
    }

    # Геттеры и сеттеры

    /**
     * @param string $path
     *
     * @return View
     */
    public function setViewsPath(string $path): View
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
     *
     * @return View
     */
    public function setLayout(string $layout): View
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
     *
     * @return View
     */
    public function setPageTitle(string $pageTitle): View
    {
        $this->pageTitle = $pageTitle;
        return $this;
    }

    /**
     * @param Controller $controller
     *
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
}
