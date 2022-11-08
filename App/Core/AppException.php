<?php

namespace App\Core;

class AppException extends \Exception {
    protected $code = null;
    protected $message = null;

    public function __construct($code, $message) {
        $this->code = $code;
        $this->message = $message;
    }
};