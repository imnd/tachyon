<?php

namespace tachyon\commands;

/**
 * @author imndsu@gmail.com
 * @copyright (c) 2026 imnd labs
 */
abstract class Command
{
    /**
     * Runs the command
     */
    abstract public function run(): void;
}
