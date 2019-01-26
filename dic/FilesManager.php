<?php
namespace tachyon\dic;

/**
 * Трэйт сеттера конфига
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
trait FilesManager
{
    /**
     * @var \tachyon\components\FilesManager $filesManager
     */
    protected $filesManager;

    /**
     * @param \tachyon\components\FilesManager $service
     * @return void
     */
    public function setFilesManager(\tachyon\components\FilesManager $service)
    {
        $this->filesManager = $service;
    }
}
