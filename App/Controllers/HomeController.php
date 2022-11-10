<?php
    namespace App\Controllers;

    use App\Core\Controller;

    class HomeController extends Controller {
        function Index($req, $res) {
           $this->render('home', 
            [
                'name' => $req->get('name'),
                'data' => ['age' => 24, 'sex' => 'male', 'birthday' => '01/25/1998']
            ]);
        }
    }