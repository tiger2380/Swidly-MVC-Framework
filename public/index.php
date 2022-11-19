<?php

session_start();
require __DIR__ . '/../bootstrap.php';

(new App\Core\App())->run();
