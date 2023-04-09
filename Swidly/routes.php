<?php

/**
 * Created a route for the API
 */

use Swidly\Middleware\AuthMiddleware;

$this->get('/themes', function($req, $res) {
    (new \Swidly\Core\Controller())->render('themes');
})->registerMiddleware(AuthMiddleware::class);