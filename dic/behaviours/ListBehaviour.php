<?php
namespace tachyon\dic\behaviours;

/**
 * Трэйт сеттера конфига
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
trait ListBehaviour
{
    /**
     * @var \tachyon\behaviours\ListBehaviour $listbehaviour
     */
    protected $listBehaviour;

    /**
     * @param \tachyon\behaviours\ListBehaviour $service
     * @return void
     */
    public function setListBehaviour(\tachyon\behaviours\ListBehaviour $service)
    {
        $this->listBehaviour = $service;
    }
}
