<?php

namespace App\Middleware;

abstract class BaseMiddleWare {
    abstract public function execute($request, $response);
}