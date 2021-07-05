<?php

use app\ServiceContainer;
use tachyon\components\Flash;
use tachyon\components\Html;
use tachyon\components\Message;
use tachyon\Config;
use tachyon\db\dbal\{
    Db, DbFactory
};
use tachyon\exceptions\ContainerException;

/**
 * @param $key
 *
 * @return mixed
 * @throws ContainerException | ReflectionException
 */
function config($key)
{
    $config = app()->get(Config::class);
    return $config->get($key);
}

/**
 * @return Db
 * @throws ContainerException | ReflectionException
 */
function db(): Db
{
    return app()->get(DbFactory::class)->getDb();
}

/**
 * @param mixed ...$params
 *
 * @return mixed
 * @throws ContainerException
 * @throws ReflectionException
 */
function flash(...$params)
{
    $flash = app()->get(Flash::class);

    if (in_array($params[0], Flash::FLASH_TYPES)) {
        return $flash->getFlash($params[0]);
    }
    return $flash->addFlash($params[0], $params[1]);
}

/**
 * @return Html
 * @throws ContainerException | ReflectionException
 */
function html(): Html
{
    return app()->get(Html::class);
}

/**
 * @param string $msg
 * @param array  $vars
 *
 * @return string
 * @throws ContainerException | ReflectionException
 */
function i18n(string $msg, array $vars = [])
{
    return app()->get(Message::class)->i18n($msg, $vars);
}

/** @return ServiceContainer */
function app(): ServiceContainer
{
    return $_SESSION['app'];
}

