<?php

namespace tachyon\db;

/**
 * Миграция
 *
 * @author imndsu@gmail.com
 * @copyright (c) 2021 IMND
 */
abstract class Migration
{
    /**
     * Runs the migration
     */
    abstract public function run(): void;
}
