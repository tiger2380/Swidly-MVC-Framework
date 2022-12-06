<?php

namespace Swidly\Core;

class Form {
    protected $action = null;
    protected $method = null;
    protected $form = '';
    protected $placeholder = '';

    public function begin($action = '', $method = 'POST') {
        echo "<form action='{$action}' method='{$method}' >";
        return $this;
    }

    public function addInput($type, $id, $name, $required = false) {
        $required = $required ? 'required' : '';
        echo "<input type='{$type}' id='{$id}' name='{$name}' placeholder='{$this->placeholder}' $required />";
    }

    public function placeholder($str) {
        $this->placeholder = $str;
        return $this;
    }

    public function end() {
        echo "</form>";
        return $this;
    }
}