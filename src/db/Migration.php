<?php

namespace tachyon\db;

/**
 * Миграция
 *
 * @author imndsu@gmail.com
 * @copyright (c) 2026 imnd labs
 */
abstract class Migration
{
    /**
     * Runs the migration
     */
    abstract public function run(): void;
}
