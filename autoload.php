<?php
include("..\\vendor\\tachyon\\exceptions\\FileNotFoundException.php");

spl_autoload_register(function($className) {
    $className = str_replace('\\', '/', $className);
    foreach (array(
        '',
        'vendor',
        'app',
    ) as $path) {
        $fileName = "../$path/$className.php";
        if (file_exists($fileName)) {
            include_once($fileName);
            return;
        }
    }
    throw new ErrorException("Класс $className не найден.");
});
