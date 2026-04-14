<?php

use Swidly\Core\Store;

$db_host = '';
$db_name = '';
$db_user = '';
$db_pass = '';
$db_charset = 'utf8mb4';
$db_collation = 'utf8mb4_unicode_ci';

if (file_exists('../db.php')) {
    require_once '../db.php';
} 

function url_origin( $s, $use_forwarded_host = false )
{
    $ssl      = ( ! empty( $s['HTTPS'] ) && $s['HTTPS'] == 'on' );
    $sp       = strtolower( $s['SERVER_PROTOCOL'] );
    $protocol = substr( $sp, 0, strpos( $sp, '/' ) ) . ( ( $ssl ) ? 's' : '' );
    $port     = $s['SERVER_PORT'];
    $port     = ( ( ! $ssl && $port=='80' ) || ( $ssl && $port=='443' ) ) ? '' : ':'.$port;
    $host     = ( $use_forwarded_host && isset( $s['HTTP_X_FORWARDED_HOST'] ) ) ? $s['HTTP_X_FORWARDED_HOST'] : ( isset( $s['HTTP_HOST'] ) ? $s['HTTP_HOST'] : null );
    $host     = isset( $host ) ? $host : $s['SERVER_NAME'] . $port;
    return $protocol . '://' . $host;
}

function full_url( $s, $use_forwarded_host = false )
{
    return url_origin( $s, $use_forwarded_host ) . $s['REQUEST_URI'];
}

$base_url = full_url($_SERVER);
if(Store::hasKey('base_url')) {
    $base_url = Store::get('base_url');
} else {
    Store::save('base_url', $base_url);
}

return [
    'app' => [
        'title' => '',
        'base_url' => $base_url,
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
        'charset' => $db_charset,
        'collation' => $db_collation,
        'prefix' => '',
    ],
    'url' => $base_url,
    'DEVELOPMENT_ENVIRONMENT' => true,
    'session_name' => 'default_session',
    'sms' => [
        'textlocal_api_key' => '',
        'sender' => 'GEMGDE',
        'default_country_code' => '',
    ],
];