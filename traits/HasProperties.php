<?php
namespace tachyon\traits;

/**
 * @author Андрей Сердюк
 * @copyright (c) 2019 IMND
 */ 
trait HasProperties
{
    /**
     * @var $properties array
     */
    protected $properties = array();

    public function setProperty($var, $val)
    {
        $this->properties[$var] = $val;
    }

    public function getProperty($var)
    {
        return $this->properties[$var] ?? null;
    }
}
