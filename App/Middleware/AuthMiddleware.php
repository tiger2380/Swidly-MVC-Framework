<?php

namespace App\Middleware;

use App\Core\AppException;

class AuthMiddleware extends BaseMiddleWare {
    public function execute($req, $res) {
        if(!isset($_SESSION[\App\Core\App::getConfig('session_name')])) {
            throw new AppException('<h2>You must be logged in to access this page</h2>');
            exit();
        }
    }
}