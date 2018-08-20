<?php
namespace tachyon\dic;

/**
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
trait XSLTProcessor
{
    /**
     * @var \XSLTProcessor $xsltProcessor
     */
    protected $xsltProcessor;

    /**
     * @param \XSLTProcessor $service
     * @return void
     */
    public function setXsltProcessor(\XSLTProcessor $service)
    {
        $this->xsltProcessor = $service;
    }

    /**
     * @return \XSLTProcessor
     */
    public function getXsltProcessor()
    {
        if (is_null($this->xsltProcessor)) {
            $this->xsltProcessor = \tachyon\dic\Container::getInstanceOf('XSLTProcessor');
        }
        return $this->xsltProcessor;
    }
}
