<?php
    namespace App\Controllers;

    use App\Core\Controller;
    use App\Core\Attributes\Route;

    class HomeController extends Controller {

        #[Route(methods: ['GET'], path: ['/', '/home'])]
        function Index($req, $res) {
           $this->render('home', 
            [
                'name' => $req->get('name'),
                'data' => ['age' => 24, 'sex' => 'male', 'birthday' => '01/25/1998']
            ]);
        }
    }