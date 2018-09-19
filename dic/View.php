<?php
namespace tachyon\dic;

/**
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
trait View
{
    /**
     * @var \tachyon\View $view
     */
    protected $view;

    /**
     * @param \tachyon\View $service
     * @return void
     */
    public function setView(\tachyon\View $service)
    {
        $this->view = $service;
    }
}
