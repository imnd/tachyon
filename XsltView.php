<?php
namespace tachyon;

/**
 * Компонент отображения на основе XSLT шаблонизации
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
class XsltView extends View
{
    use \tachyon\dic\XSLTProcessor;

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
        if ($return)
            return $output;
        else    
            echo $output;
    }

    private function arrayToXML($inpArray, $rootTag='root', $innerTag='element')
    {
         $xml = "<$rootTag>";
         foreach ($inpArray as $key => $val) {
             if (is_numeric($key))
                 $tag = $innerTag;
             else    
                 $tag = $key;
                 
             if (is_array($val))
                 $xml .= $this->arrayToXML($val, $tag);
             else
                 $xml .= "<$tag>$val</$tag>";
         }
         return $xml .= "</$rootTag>";
    }
    
    /**
     * XsltTransform
     */
    private function _xsltTransform($xml, $tpl)
    {
        $xsl = $this->get('Dom');
        $xsl->load("{$this->viewsPath}/$tpl.xsl");
        $proc = $this->xsltProcessor;
        $proc->importStylesheet($xsl);
        $doc = $this->get('Dom');
        $doc->loadXML($xml);
        return $proc->transformToXML($doc);
    }
}
