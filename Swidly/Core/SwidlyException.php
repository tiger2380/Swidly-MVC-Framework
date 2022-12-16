<?php

namespace Swidly\Core;

class SwidlyException extends \Exception {
    protected $code = null;
    protected $message = null;

    /**
     * @param string $message
     * @param int $code
     */
    public function __construct(string $message, int $code = 200) {
        $this->code = $code;
        $this->message = $message;
    }
};