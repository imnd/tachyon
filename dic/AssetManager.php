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
     * @var \tachyon\components\AssetManager $Alias
     */
    protected $assetManager;

    /**
     * @param \tachyon\components\AssetManager $service
     * @return void
     */
    public function setAssetManager(\tachyon\components\AssetManager $service)
    {
        $this->assetManager = $service;
    }
}
