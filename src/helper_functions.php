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
 * @param string $message
 * @param string $type
 *
 * @return mixed
 * @throws ContainerException | ReflectionException
 */
function flash(string $message, string $type)
{
    return app()->get(Flash::class)->addFlash($message, $type);
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

