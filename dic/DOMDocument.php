<?php
namespace tachyon\dic;

/**
 * Трэйт сеттера DOM
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
trait DomDocument
{
    /**
     * @var \DOMDocument $domDocument
     */
    protected $domDocument;

    /**
     * @return \DOMDocument
     */
    public function setDomDocument(\DOMDocument $service)
    {
        $this->domDocument = $service;
    }
}