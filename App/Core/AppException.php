<?php

namespace App\Core;

class AppException extends \Exception {
    protected $code = null;
    protected $message = null;

    public function __construct($message, $code = 200) {
        $this->code = $code;
        $this->message = $message;
    }
};