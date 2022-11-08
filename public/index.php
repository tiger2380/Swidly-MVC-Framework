<?php
    session_start();
    require __DIR__ . '/../vendor/autoload.php';

    (new App\Core\App())->run();