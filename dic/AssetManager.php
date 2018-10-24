<?php
namespace tachyon\dic;

/**
 * Трэйт сеттера конфига
 * 
 * @author Андрей Сердюк
 * @copyright (c) 2018 IMND
 */
trait AssetManager
{
    /**
     * @var \tachyon\db\Alias $Alias
     */
    protected $assetManager;

    /**
     * @param \tachyon\db\Alias $service
     * @return void
     */
    public function setAssetManager(\tachyon\components\AssetManager $service)
    {
        $this->assetManager = $service;
    }
}
