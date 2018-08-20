<?php
namespace tachyon\dic\behaviours;

/**
 * Трэйт сеттера конфига
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
trait DateTime
{
    /**
     * @var \tachyon\behaviours\DateTime $dateTimeBehaviour
     */
    protected $dateTimeBehaviour;

    /**
     * @param \tachyon\behaviours\DateTime $service
     * @return void
     */
    public function setDateTimeBehaviour(\tachyon\behaviours\DateTime $service)
    {
        $this->dateTimeBehaviour = $service;
    }

    /**
     * @return \tachyon\behaviours\DateTime
     */
    public function getDateTimeBehaviour()
    {
        if (is_null($this->dateTimeBehaviour)) {
            $this->dateTimeBehaviour = \tachyon\dic\Container::getInstanceOf('DateTime');
        }
        return $this->dateTimeBehaviour;
    }
}
