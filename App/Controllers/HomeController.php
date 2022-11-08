<?php
    namespace App\Controllers;

    use App\Core\Controller;

    class HomeController extends Controller {
        function Index($req, $res) {
           $this->render('home', ['person_name' => $req->get('person_name')]);
        }
    }