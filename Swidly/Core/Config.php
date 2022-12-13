<?php

return [
    'app' => [
        'title' =>'Not Another PHP MVC',
        'favicon' => '',
        'description' => '',
        'single_page' => true
    ],
    'default_lang' => 'en',
    'theme' => 'single_page',
    'db' => [
        'host'     => '127.0.0.1',
        'database' => 'blog',
        'username' => 'root',
        'password' => 'password',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
    ],
    'user' => [
        'table' => 'users',
        'auth_field' => 'email'
    ],
    'url' => '',
    'DEVELOPMENT_ENVIRONMENT' => true,
    'session_name' => 'default_session'
];