<?php
namespace tachyon\dic;

/**
 * Трэйт сеттера компонента сообщений
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
trait Message
{
    /**
     * @var \tachyon\components\Message $msg
     */
    protected $msg;

    /**
     * @param \tachyon\components\Message $service
     * @return void
     */
    public function setMsg(\tachyon\components\Message $service)
    {
        $this->msg = $service;
    }
}
