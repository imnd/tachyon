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
    protected $dateTime;

    /**
     * @param \tachyon\behaviours\DateTime $service
     * @return void
     */
    public function setDateTime(\tachyon\behaviours\DateTime $service)
    {
        $this->dateTime = $service;
    }

    /**
     * @return \tachyon\behaviours\DateTime
     */
    public function getDateTime()
    {
        if (is_null($this->dateTimeBehaviour)) {
            $this->dateTimeBehaviour = \tachyon\dic\Container::getInstanceOf('DateTime');
        }
        return $this->dateTimeBehaviour;
    }
}
