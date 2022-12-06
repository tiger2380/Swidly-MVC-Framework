<?php

namespace Swidly\Middleware;

abstract class BaseMiddleWare {
    abstract public function execute($request, $response);
}