<?php

define('APP_PATH', __DIR__.'/Swidly/');
spl_autoload_register('autoLoader');

function autoLoader($className) {
    $paths = [
        APP_PATH.'Controllers/',
        APP_PATH.'Core/',
        APP_PATH.'Middleware/',
        APP_PATH.'Models/',
    ];

    $filepath = __DIR__.'/'.str_replace('\\', '/', $className).'.php';
    if(file_exists($filepath)) {
        require_once $filepath;
    } else {
        foreach ($paths as $path) {
            $filepath = $path.str_replace('\\', '/', $className).'.php';
            if (file_exists($filepath)) {
                require_once $filepath;
                break;
            }
        }
    }
}