<?php

use app\ServiceContainer;
use tachyon\components\{
    Flash, Html, Lang, Message
};
use tachyon\db\dbal\{
    Db, DbFactory
};
use tachyon\{
    Config, View
};

function config(string $key): mixed
{
    $config = app()->get(Config::class);
    return $config->get($key);
}

function view(): View
{
    return app()->get(View::class);
}

function db(): Db
{
    return app()->get(DbFactory::class)->getDb();
}

function flash(mixed ...$params): mixed
{
    $flash = app()->get(Flash::class);

    if (in_array($params[0], Flash::FLASH_TYPES)) {
        return $flash->getFlash($params[0]);
    }
    return $flash->addFlash($params[0], $params[1]);
}

function html(): Html
{
    return app()->get(Html::class);
}

function lang(): Lang
{
    return app()->get(Lang::class);
}

function t(string $msg, array $vars = []): string
{
    return app()->get(Message::class)->t($msg, $vars);
}

function app(): ServiceContainer
{
    return $_SESSION['app'];
}
