<?php
include('exceptions\FileNotFoundException.php');

spl_autoload_register(function($className) {
    $className = str_replace('\\', '/', $className);
    foreach ([
        '',
        'vendor',
        'app',
    ] as $path) {
        $fileName = "../$path/$className.php";
        if (file_exists($fileName)) {
            include_once($fileName);
            return;
        }
    }
    throw new \tachyon\exceptions\FileNotFoundException("Class $className not found.");
});
