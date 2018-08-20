<?php
namespace tachyon\dic;

/**
 * Трэйт сеттера конфига
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
trait DOMDocument
{
    /**
     * @return \DOMDocument
     */
    public function getDom()
    {
        return \tachyon\dic\Container::getInstanceOf('DOMDocument');
    }
}
