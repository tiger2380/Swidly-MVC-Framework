<?php

class Test {
    public function __construct() {
        echo "Hello World!";
    }

    public function run($file = null) {
        if ($file) {
            echo "Running $file";
        } else {
            echo "Running all tests";
        }
    }

    public function __destruct() {
        echo "Bye World!";
    }
}