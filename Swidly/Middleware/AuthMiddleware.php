<?php

namespace Swidly\Middleware;

use Swidly\Core\SwidlyException;

class AuthMiddleware extends BaseMiddleWare {
    /**
     * @throws SwidlyException
     */
    public function execute($request, $response) {
        if(!isset($_SESSION[\Swidly\Core\Swidly::getConfig('session_name')])) {
            throw new SwidlyException('<h2>You must be logged in to access this page</h2>');
        }
    }
}