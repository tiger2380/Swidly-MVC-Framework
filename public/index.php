<?php

session_start();
require __DIR__ . '/../bootstrap.php';

(new Swidly\Core\Swidly())->run();
