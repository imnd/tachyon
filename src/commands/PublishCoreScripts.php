<?php

namespace tachyon\commands;

use tachyon\components\AssetManager;

/**
 * Копирует js-скрипты из папки фреймворка в папку public/assets/js
 * usage: tachyon publish-core-scripts
 *
 * @author Андрей Сердюк
 * @copyright (c) 2021 IMND
 */
class PublishCoreScripts extends Command
{
    private $assetManager;

    /**
     * @param AssetManager $assetManager
     * @return void
     */
    public function __construct(AssetManager $assetManager)
    {
        $this->assetManager = $assetManager;
    }

    /**
     * @inheritDoc
     */
    public function run(): void
    {
        $this->assetManager->coreJs()->publishSeparated();
        echo "done";
    }
}
