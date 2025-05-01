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
 * @author imndsu@gmail.com
 */
class View
{
    use HasOwner, HasProperties;

    /**
     * the controller that invokes view
     */
    protected ?Controller $controller = null;
    /**
     * views path
     */
    protected string $viewsPath;
    /**
     * views path
     */
    protected string $appViewsPath;
    /**
     * layout path
     */
    protected string $layoutPath;
    /**
     * layout name
     */
    protected string $layout = '';
    protected string $pageTitle = '';

    /**
     * persistent variables between drawing inheritable layouts
     */
    protected array $layoutVars = [];
    protected Request $request;

    public function __construct(
        Config $config,
        protected Env $env,
        protected AssetManager $assetManager,
        protected Message $msg,
        protected Html $html,
        protected Flash $flash
    ) {
        $this->appViewsPath = $this->viewsPath = $config->get('base_path') . Config::APP_DIR . 'app/views';
    }

    /**
     * displays the view file, passing it the parameters as an array in the specified layout
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
     * displays the view $view passing it the parameters $vars in an array
     *
     * @param string  $viewName view file name
     * @param array   $vars     view variables
     * @param boolean $return   show or return
     */
    public function display(
        string $viewName,
        array $vars = [],
        bool $return = false
    ): ?string {
        $contents = $this->_view("{$this->viewsPath}/$viewName", $vars);
        $contents = $this->_displayExtends($contents, $vars, false);

        if ($return) {
            return $contents;
        }
        echo $contents;

        return null;
    }

    private function _displayExtends(string $contents, array $vars, bool $layout = true): string
    {
        $keyWord = 'extends';
        if (false !== $extendsPos = strpos($contents, "@$keyWord")) {
            $start = $extendsPos + strlen("@$keyWord") + 2;
            $end = strpos($contents, "'", $start);
            // set the parent layout
            $this->layout = substr($contents, $start, $end - $start);
            // remove the tag '@extends'
            $contents = substr($contents, $end + 2);
            if ($layout) {
                // render the parent layout
                $contents = $this->_displayLayout($contents, $vars);
            }
        }
        return $contents;
    }

    private function _displayLayout(string $viewContents, array $vars): string
    {
        $contents = $this->_view("{$this->layoutPath}/{$this->layout}", $vars);
        $contents = $this->_displayExtends($contents, $vars);
        $contents = $this->_replaceTag($contents, $viewContents, '@contents');
        $this->assetManager->finalize($contents);

        return $contents;
    }

    /**
     * replaces the text of the tag $tag in the text $textToReplace to $text
     */
    private function _replaceTag(string $textToReplace, string $text, string $tag): string
    {
        $tagPos = strpos($textToReplace, $tag);
        return substr($textToReplace, 0, $tagPos) . $text . substr($textToReplace, $tagPos + strlen($tag) + 1);
    }

    private function _view(string $path, array $vars = []): false | string
    {
        if (!file_exists($filePath = "$path.php")) {
            throw new ViewException("{t('No view file found')}: \"$filePath\"");
        }
        $buffer = file_get_contents($filePath);
        if (false !== strpos($buffer, '{{')) {
            $tempViewFilePath = "{$_SERVER['DOCUMENT_ROOT']}/../runtime/templates/" . md5($filePath) . '.php';
            if (
                   // в debug mode скомпилированные шаблоны переписываются всегда
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
            // layout render
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
            // rewrite the values of the variables in the child layout
            foreach ($this->layoutVars as $name => $var) {
                if (isset($this->layoutVars[$name])) {
                    unset($layoutVars[$name]);
                }
            }
            // collect all variables declared in layout
            $this->layoutVars = array_merge($this->layoutVars, $layoutVars);
        } else {
            // view render
            extract($this->layoutVars);
            require($filePath);
        }
        $contents = ob_get_contents();
        ob_end_clean();

        return $contents;
    }

    /**
     * js code connection
     *
     * @param string $source code text either path
     * @param string $mode   inline or external file
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
     * run the widget on the page
     */
    public function widget(array $params)
    {
        $class = $params['class'];
        unset($params['class']);
        $widget = app()->get($class);
        $widget->setParameters($params);
        $widget->setOwner($this);
        $controller = $this->controller ?: $params['controller'] ?? null;
        $widget->setController($controller);
        return $widget->run();
    }

    /**
     * escape output
     */
    public function escape(string $text = null): string
    {
        return htmlspecialchars($text ?: '');
    }

    # getters and setters

    public function setViewsPath(string $path): self
    {
        $this->viewsPath = $path;
        return $this;
    }

    public function getViewsPath(): string
    {
        return $this->viewsPath;
    }

    public function getLayout(): string
    {
        return $this->layout;
    }

    public function setLayout(string $layout): self
    {
        $this->layout = $layout;
        return $this;
    }

    public function getPageTitle(): string
    {
        return $this->pageTitle;
    }

    public function setPageTitle(string $pageTitle): self
    {
        $this->pageTitle = $pageTitle;
        return $this;
    }

    public function setRequest(Request $request): self
    {
        $this->request = $request;
        return $this;
    }

    public function setController(Controller $controller): self
    {
        $this->controller = $controller;
        return $this;
    }

    public function getController(): Controller
    {
        return $this->controller;
    }
}
