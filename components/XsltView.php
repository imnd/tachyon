<?php
namespace tachyon\components;

use DOMDocument,
    XSLTProcessor;

/**
 * Компонент отображения на основе XSLT шаблонизации
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
class XsltView extends View
{
    /**
     * @var DOMDocument $domDocument
     */
    protected $domDocument;
    /**
     * @var XSLTProcessor $xsltProcessor
     */
    protected $xsltProcessor;

    /**
     * @param boolean string integer array mixed 
     */
    public function __construct(DOMDocument $domDocument, XSLTProcessor $xsltProcessor, ...$params)
    {
        $this->domDocument = $domDocument;
        $this->xsltProcessor = $xsltProcessor;

        parent::__construct(...$params);
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
    public function layout($view, array $vars=array())
    {
        require $this->getLayoutPath() . '/head.php';
        $xml = $this->arrayToXML($vars, 'root');
        echo $this->_xsltTransform($xml, $view);
        require $this->getLayoutPath() . '/foot.php';
    }

    /**
     * Отображает файл представления 
     * передавая ему параметря в виде массива
     * 
     * @param $view string
     * @param $vars array 
     */
    public function display($view, array $vars=array(), $return=false)
    {
        $xml = $this->arrayToXML($vars, 'root');
        $output = $this->_xsltTransform($xml, $view);
        if ($return) {
            return $output;
        }
        echo $output;
    }

    private function arrayToXML($inpArray, $rootTag='root', $innerTag='element')
    {
         $xml = "<$rootTag>";
         foreach ($inpArray as $key => $val) {
             $tag = is_numeric($key) ? $innerTag : $key;
             $xml .= is_array($val) ? $this->arrayToXML($val, $tag) : "<$tag>$val</$tag>";
         }
         return "$xml</$rootTag>";
    }
    
    /**
     * XsltTransform
     */
    private function _xsltTransform($xml, $tpl)
    {
        $this->domDocument->load("{$this->viewsPath}/$tpl.xsl");
        $this->xsltProcessor->importStylesheet($this->domDocument);
        $this->domDocument->loadXML($xml);
        return $this->xsltProcessor->transformToXML($this->domDocument);
    }
}
