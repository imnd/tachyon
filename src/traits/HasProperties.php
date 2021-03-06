<?php
namespace tachyon\traits;

/**
 * @author Андрей Сердюк
 * @copyright (c) 2020 IMND
 */
trait HasProperties
{
    /**
     * @var $properties array
     */
    protected array $properties = [];

    /**
     * @param $var
     * @param $val
     */
    public function setProperty($var, $val): void
    {
        $this->properties[$var] = $val;
    }

    /**
     * @param $var
     *
     * @return mixed|null
     */
    public function getProperty($var)
    {
        return $this->properties[$var] ?? null;
    }
}
