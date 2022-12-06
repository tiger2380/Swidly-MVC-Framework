<?php

namespace Swidly\Core;

class SwidlyException extends \Exception {
    protected $code = null;
    protected $message = null;

    public function __construct($message, $code = 200) {
        $this->code = $code;
        $this->message = $message;
    }
};