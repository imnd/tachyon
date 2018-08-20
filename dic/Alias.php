<?php
namespace tachyon\dic;

/**
 * Трэйт сеттера конфига
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
trait Alias
{
    /**
     * @var \tachyon\db\Alias $Alias
     */
    protected $alias;

    /**
     * @param \tachyon\db\Alias $service
     * @return void
     */
    public function setAlias(\tachyon\db\Alias $service)
    {
        $this->alias = $service;
    }

    /**
     * @return \tachyon\db\Alias
     */
    public function getAlias()
    {
        if (is_null($this->alias)) {
            $this->alias = \tachyon\dic\Container::getInstanceOf('Alias');
        }
        return $this->alias;
    }
}
