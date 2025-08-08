<?php
spl_autoload_register('autoLoader');

const SWIDLY_VERSION = '0.0.1';
const APP_ROOT = __DIR__;
define('APP_BASE', basename(APP_ROOT));
const SWIDLY_ROOT = APP_ROOT . '/Swidly/';
const SWIDLY_CORE = SWIDLY_ROOT . '/Core';

if(file_exists(SWIDLY_CORE.'/helpers.php')) {
    require_once SWIDLY_CORE . '/helpers.php';
} else {
    echo 'helpers file doesn\'t exists';
}

\Swidly\Core\Store::start();

function autoLoader($className): void
{
    $paths = [
        SWIDLY_ROOT.'Controllers/',
        SWIDLY_ROOT.'Core/',
        SWIDLY_ROOT.'Middleware/',
        SWIDLY_ROOT.'models/',
        SWIDLY_ROOT.'Libs/',
        SWIDLY_ROOT.'Helpers/',
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