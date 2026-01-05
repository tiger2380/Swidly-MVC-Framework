<?php

namespace Swidly\Core;


abstract class Controller
{
    public View $view;
    public function __construct() {
        $this->view = new View();
    }
    abstract public function Index($req, $res);

    public function render($template, $data = []) {
        $this->view->registerCommonComponents();
        echo $this->view->render($template, $data);
    }
}