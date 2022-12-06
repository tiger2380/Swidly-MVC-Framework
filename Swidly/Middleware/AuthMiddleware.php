<?php

namespace Swidly\Middleware;

use Swidly\Core\SwidlyException;

class AuthMiddleware extends BaseMiddleWare {
    public function execute($req, $res) {
        if(!isset($_SESSION[\Swidly\Core\Swidly::getConfig('session_name')])) {
            throw new SwidlyException('<h2>You must be logged in to access this page</h2>');
            exit();
        }
    }
}