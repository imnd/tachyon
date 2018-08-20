<?php
namespace tachyon\dic;

/**
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
trait Validator
{
    /**
     * @var \tachyon\components\Validator $validator
     */
    protected $validator;

    /**
     * @param \tachyon\components\Validator $service
     * @return void
     */
    public function setValidator(\tachyon\components\Validator $service)
    {
        $this->validator = $service;
    }

    /**
     * @return \tachyon\components\Validator
     */
    public function getValidator()
    {
        if (is_null($this->validator)) {
            $this->validator = \tachyon\dic\Container::getInstanceOf('Validator');
        }
        return $this->validator;
    }
}
