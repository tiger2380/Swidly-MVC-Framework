<?php

namespace Swidly\Core;

class SwidlyException extends \Exception {
    protected $code = null;
    protected $message = null;

    /**
     * @param string $message
     * @param int $code
     */
    public function __construct(string $message, int $code = 500) {
        parent::__construct($message, $code);
        $this->code = $code;
        $this->message = $message;
    }

    /**
     * @return int
     */
    public function getStatusCode() : int {
        return $this->code;
    }

    /**
     * @return string
     */
    public function getStatusMessage() : string {
        return $this->message;
    }

    /**
     * @return string
     */
    public function __toString() : string {
        return $this->message;
    }
};