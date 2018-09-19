<?php
namespace tachyon\dic;

/**
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
trait Html
{
    /**
     * Компонент построителя html-кода
     * @var \tachyon\components\html\Html $html
     */
    protected $html;

    /**
     * @param \tachyon\components\html\Html $service
     * @return void
     */
    public function setHtml(\tachyon\components\html\Html $service)
    {
        $this->html = $service;
    }
}
