<?php
namespace tachyon\dic\behaviours;

/**
 * Трэйт сеттера конфига
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
trait Active
{
    /**
     * @var \tachyon\behaviours\Active $activeBehaviour
     */
    protected $activeBehaviour;

    /**
     * @param \tachyon\behaviours\Active $service
     * @return void
     */
    public function setActiveBehaviour(\tachyon\behaviours\Active $service)
    {
        $this->activeBehaviour = $service;
    }
}
