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
}
