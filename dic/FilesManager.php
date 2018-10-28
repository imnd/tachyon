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
     * @var \tachyon\db\Alias $Alias
     */
    protected $filesManager;

    /**
     * @param \tachyon\db\Alias $service
     * @return void
     */
    public function setFilesManager(\tachyon\components\FilesManager $service)
    {
        $this->filesManager = $service;
    }
}
