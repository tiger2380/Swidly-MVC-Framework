<?php

$db_host = '';
$db_name = '';
$db_user = '';
$db_pass = '';
$db_charset = 'utf8mb4';
$db_collation = 'utf8mb4_unicode_ci';

if (file_exists('db.php')) {
    require_once 'db.php';
} 

return [
    'app' => [
        'title' => 'Not Another PHP MVC',
        'favicon' => '',
        'description' => '',
        'single_page' => false
    ],
    'default_lang' => 'en',
    'theme' => 'default',
    'db' => [
        'host'     => $db_host,
        'database' => $db_name,
        'username' => $db_user,
        'password' => $db_pass,
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
    ],
    'url' => '',
    'DEVELOPMENT_ENVIRONMENT' => true,
    'session_name' => 'default_session'
];