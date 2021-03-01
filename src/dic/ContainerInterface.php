<?php
namespace tachyon\dic;

/**
 * PSR-11: Container interface
 */
interface ContainerInterface
{
    public function get($id);
    public function has($id): bool;
}