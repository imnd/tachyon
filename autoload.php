<?php

function __autoload($className)
{
    $basePath = '..';
    $className = str_replace('\\', '/', $className);
    foreach (array(
        '',
        'vendor',
        'app',
    ) as $path) {
        $fileName = "$basePath/$path/$className.php";
        if (file_exists($fileName)) {
            include_once($fileName);
            break;
        }
    }
}