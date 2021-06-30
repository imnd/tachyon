<?php

namespace tachyon\commands;

/**
 * @author Андрей Сердюк
 * @copyright (c) 2021 IMND
 */
abstract class Command
{
    /**
     * Runs the command
     */
    abstract public function run(): void;
}
