<?php
spl_autoload_register('autoLoader');

const APP_VERSION = '0.0.1';
const APP_ROOT = __DIR__;
define('APP_BASE', basename(APP_ROOT));
const APP_PATH = APP_ROOT . '/Swidly/';
const APP_CORE = APP_PATH . '/Core';
define('SWIDLY_CORE', true);

if(file_exists(APP_CORE.'/helpers.php')) {
    require_once APP_CORE . '/helpers.php';
} else {
    echo 'helpers file doesn\'t exists';
}

function autoLoader($className): void
{
    $paths = [
        APP_PATH.'Controllers/',
        APP_PATH.'Core/',
        APP_PATH.'Middleware/',
        APP_PATH.'models/',
        APP_PATH.'Libs/',
        APP_PATH.'Helpers/',
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