<?php

namespace tachyon;

use tachyon\dic\Container;
use tachyon\components\{
    AssetManager,
    Message,
    Html,
    Flash
};
use tachyon\traits\{
    HasOwner,
    HasProperties
};
use tachyon\exceptions\ViewException;

/**
 * class View
 * Компонент отображения
 *
 * @author Андрей Сердюк
 * @copyright (c) 2020 IMND
 */
class View
{
    use HasOwner, HasProperties;

    /**
     * Контроллер, вызывающий вью
     *
     * @var Controller $controller
     */
    protected Controller $controller;
    /**
     * Путь к отображениям
     *
     * @var string $rootViewsPath
     */
    protected string $viewsPath;
    /**
     * Путь к отображениям
     *
     * @var string $rootViewsPath
     */
    protected string $appViewsPath;
    /**
     * Путь к папке лэйаута
     *
     * @var string $layoutPath
     */
    protected string $layoutPath;
    /**
     * Имя лэйаута
     *
     * @var string $layoutPath
     */
    protected string $layout = '';
    /**
     * Заголовок страницы
     *
     * @var string $pageTitle
     */
    protected string $pageTitle = '';

    /**
     * @var AssetManager $assetManager
     */
    protected AssetManager $assetManager;
    /**
     * @var Message $msg
     */
    protected Message $msg;
    /**
     * Компонент построителя html-кода
     *
     * @var Html $html
     */
    protected Html $html;
    /**
     * @var Flash
     */
    protected Flash $flash;
    /**
     * @var Env $env
     */
    protected $env;

    /**
     * Сохраняем переменные между отрисовкой наследуемых лэйаутов
     */
    protected array $layoutVars = [];
    /**
     * @var Request
     */
    protected Request $request;

    /**
     * @param Env          $env
     * @param Config       $config
     * @param AssetManager $assetManager
     * @param Message      $msg
     * @param Html         $html
     * @param Flash        $flash
     */
    public function __construct(
        Env $env,
        Config $config,
        AssetManager $assetManager,
        Message $msg,
        Html $html,
        Flash $flash
    ) {
        $this->env = $env;
        $this->appViewsPath = $this->viewsPath = $config->get('base_path') . Config::APP_DIR . 'app/views';
        $this->assetManager = $assetManager;
        $this->msg = $msg;
        $this->html = $html;
        $this->flash = $flash;
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
        $contents = $this->display($viewsPath, $vars, true);
        if ($this->layout) {
            echo $this->_displayLayout($contents, $vars);
        } else {
            echo $contents;
        }
    }

    /**
     * Отображает файл представления $view
     * передавая ему параметры $vars в виде массива
     *
     * @param string  $viewName имя файла вью
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
        $contents = $this->_displayExtends($contents, $vars, false);

        if ($return) {
            return $contents;
        }
        echo $contents;
    }

    private function _displayExtends(string $contents, array $vars, bool $layout = true): string
    {
        $keyWord = 'extends';
        if (false !== $extendsPos = strpos($contents, "@$keyWord")) {
            $start = $extendsPos + strlen("@$keyWord") + 2;
            $end = strpos($contents, "'", $start);
            // устанавливаем родительский лэйаут
            $this->layout = substr($contents, $start, $end - $start);
            // убираем тег '@extends'
            $contents = substr($contents, $end + 2);
            if ($layout) {
                // отрисовываем родительский лэйаут
                $contents = $this->_displayLayout($contents, $vars);
            }
        }
        return $contents;
    }

    /**
     * @param string $viewContents
     * @param array  $vars
     *
     * @return string
     * @throws ViewException
     */
    private function _displayLayout(string $viewContents, array $vars): string
    {
        $contents = $this->_view("{$this->layoutPath}/{$this->layout}", $vars);
        $contents = $this->_displayExtends($contents, $vars);
        $contents = $this->_replaceTag($contents, $viewContents, '@contents');
        $this->assetManager->finalize($contents);

        return $contents;
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

    private function _view(string $filePath, array $vars = [])
    {
        $filePath = "$filePath.php";
        if (!file_exists($filePath)) {
            throw new ViewException("{$this->msg->i18n('No view file found')}: \"$filePath\"");
        }
        $buffer = file_get_contents($filePath);
        if (false !== strpos($buffer, '{{')) {
            $tempViewFilePath = "{$_SERVER['DOCUMENT_ROOT']}/../runtime/templates/" . md5($filePath) . '.php';
            if (
                   // в debug mode скомпиленные шаблоны переписываются всегда
                   !$this->env->isProduction()
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
     * Запуск виджета на странице
     */
    public function widget(array $params)
    {
        $class = $params['class'];
        unset($params['class']);
        $widget = app()->get($class);
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
     * @return self
     */
    public function setViewsPath(string $path): self
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
     * @return self
     */
    public function setLayout(string $layout): self
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
     * @return self
     */
    public function setPageTitle(string $pageTitle): self
    {
        $this->pageTitle = $pageTitle;
        return $this;
    }

    /**
     * @param Controller $controller
     *
     * @return self
     */
    public function setController(Controller $controller): self
    {
        $this->controller = $controller;
        return $this;
    }

    /**
     * @param Request $request
     *
     * @return self
     */
    public function setRequest(Request $request): self
    {
        $this->request = $request;
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
