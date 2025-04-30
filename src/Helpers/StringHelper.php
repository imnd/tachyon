<?php

namespace tachyon\Helpers;

/**
 * @author imndsu@gmail.com
 */
class StringHelper
{
    /**
     * Convert kebab-case to camelCase
     */
    public static function kebabToCamel(string $string): string
    {
        return str_replace(' ', '', ucwords(str_replace('-', ' ', $string)));
    }

    /**
     * Convert snake_case to camelCase
     */
    public static function snakeToCamel(string $string): string
    {
        $arr = array_map(
            static fn($elem) => ucfirst($elem),
            explode('_', $string)
        );
        return lcfirst(implode('', $arr));
    }

    /**
     * Convert camelCase to snake_case
     */
    public static function camelToSnake(string $string): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $string));
    }
}
