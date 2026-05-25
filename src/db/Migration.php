<?php

namespace tachyon\db;

/**
 * Migration
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
