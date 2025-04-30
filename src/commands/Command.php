<?php

namespace tachyon\commands;

/**
 * @author imndsu@gmail.com
 * @copyright (c) 2021 IMND
 */
abstract class Command
{
    /**
     * Runs the command
     */
    abstract public function run(): void;
}
