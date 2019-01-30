<?php
namespace tachyon\dic;

/**
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
trait Terms
{
    /**
     * @var \tachyon\db\Terms $terms
     */
    protected $terms;

    /**
     * @param \tachyon\db\Terms $service
     * @return void
     */
    public function setTerms(\tachyon\db\Terms $service)
    {
        $this->terms = $service;
    }
}
