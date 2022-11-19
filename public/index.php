<?php

session_start();
require __DIR__ . '/../bootstrap.php';
//require __DIR__ . '/../vendor/autoload.php';

(new App\Core\App())->run();
