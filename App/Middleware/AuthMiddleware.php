<?php

namespace App\Middleware;

class AuthMiddleware extends BaseMiddleWare {
    public function execute($req, $res) {
        if(!isset($_SESSION[\App\Core\App::getConfig('session_name')])) {
            $res->setContent('<h2>You must be logged in to access this page</h2>')->content();
            exit();
        }
    }
}