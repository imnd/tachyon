<?php

namespace tachyon\components;

use DOMDocument,
    XSLTProcessor,
    tachyon\View;

/**
 * View component based on XSLT templates
 *
 * @author imndsu@gmail.com
 */
class XsltView extends View
{
    public function __construct(
        protected DOMDocument $domDocument,
        protected XSLTProcessor $xsltProcessor,
        ...$params
    ) {
        parent::__construct(...$params);
    }

    /**
     * Renders view file
     * passing parameters as array in specified layout
     */
    public function view(string $viewsPath, array $vars = []): void
    {
        require "{$this->getLayoutPath()}/head.php";
        $xml = $this->arrayToXML($vars);
        echo $this->xsltTransform($xml, $viewsPath);
        require "{$this->getLayoutPath()}/foot.php";
    }

    /**
     * Renders view file passing parameters as array
     */
    public function display(string $viewName, array $vars = [], bool $return = false): ?string
    {
        $xml = $this->arrayToXML($vars);
        $output = $this->xsltTransform($xml, $viewName);
        if ($return) {
            return $output;
        }
        echo $output;
    }

    private function arrayToXML(
        array $inpArray,
        string $rootTag = 'root',
    ): string
    {
        $xml = "<$rootTag>";
        foreach ($inpArray as $key => $val) {
            $tag = is_numeric($key) ? 'element' : $key;
            $xml .= is_array($val) ? $this->arrayToXML($val, $tag) : "<$tag>$val</$tag>";
        }

        return "$xml</$rootTag>";
    }

    private function xsltTransform(string $xml, string $tpl): false|string
    {
        $this->domDocument->load("{$this->viewsPath}/$tpl.xsl");
        $this->xsltProcessor->importStylesheet($this->domDocument);
        $this->domDocument->loadXML($xml);

        return $this->xsltProcessor->transformToXML($this->domDocument);
    }
}
