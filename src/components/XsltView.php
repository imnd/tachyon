<?php

namespace tachyon\components;

use DOMDocument,
    XSLTProcessor,
    tachyon\View;

/**
 * Компонент отображения на основе XSLT шаблонизации
 *
 * @author imndsu@gmail.com
 */
class XsltView extends View
{
    /**
     * @var DOMDocument $domDocument
     */
    protected DOMDocument $domDocument;
    /**
     * @var XSLTProcessor $xsltProcessor
     */
    protected XSLTProcessor $xsltProcessor;

    /**
     * @param DOMDocument   $domDocument
     * @param XSLTProcessor $xsltProcessor
     * @param array         $params
     */
    public function __construct(
        DOMDocument $domDocument,
        XSLTProcessor $xsltProcessor,
        ...$params
    ) {
        $this->domDocument = $domDocument;
        $this->xsltProcessor = $xsltProcessor;
        parent::__construct(...$params);
    }

    /**
     * Отображает файл представления
     * передавая ему параметря в виде массива в заданном лэйауте
     *
     * @param string $view
     * @param array $vars
     *
     * @return void
     */
    public function view(string $view, array $vars = []): void
    {
        require "{$this->getLayoutPath()}/head.php";
        $xml = $this->arrayToXML($vars, 'root');
        echo $this->_xsltTransform($xml, $view);
        require "{$this->getLayoutPath()}/foot.php";
    }

    /**
     * Отображает файл представления, передавая ему параметря в виде массива
     *
     * @param string $view
     * @param array $vars
     * @param bool $return
     *
     * @return mixed
     */
    public function display(string $view, array $vars = [], bool $return = false)
    {
        $xml = $this->arrayToXML($vars, 'root');
        $output = $this->_xsltTransform($xml, $view);
        if ($return) {
            return $output;
        }
        echo $output;
    }

    /**
     * @param array  $inpArray
     * @param string $rootTag
     * @param string $innerTag
     *
     * @return string
     */
    private function arrayToXML(
        array $inpArray,
        string $rootTag = 'root',
        string $innerTag = 'element'
    ): string
    {
        $xml = "<$rootTag>";
        foreach ($inpArray as $key => $val) {
            $tag = is_numeric($key) ? $innerTag : $key;
            $xml .= is_array($val) ? $this->arrayToXML($val, $tag) : "<$tag>$val</$tag>";
        }

        return "$xml</$rootTag>";
    }

    /**
     * @param string $xml
     * @param string $tpl
     *
     * @return false|string
     */
    private function _xsltTransform(string $xml, string $tpl)
    {
        $this->domDocument->load("{$this->viewsPath}/$tpl.xsl");
        $this->xsltProcessor->importStylesheet($this->domDocument);
        $this->domDocument->loadXML($xml);

        return $this->xsltProcessor->transformToXML($this->domDocument);
    }
}
