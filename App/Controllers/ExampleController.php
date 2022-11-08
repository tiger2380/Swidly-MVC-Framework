<?php
    namespace App\Controllers;

    use App\Core\Controller;

    class ExampleController extends Controller {
        function Index($req, $res) {
           $this->render('example');
        }
    }