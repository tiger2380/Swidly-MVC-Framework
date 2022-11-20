<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Attributes\Middleware;
use App\Core\Controller;
use App\Core\Attributes\Route;
use App\Middleware\AuthMiddleware;

class HomeController extends Controller {

    #[Route(methods: ['GET'], path: ['/', '/home'])]
    #[Middleware(AuthMiddleware::class)]
    function Index($req, $res) {
        $this->render('home', 
        [
            'name' => $req->get('name'),
            'data' => ['age' => 24, 'sex' => 'male', 'birthday' => '01/25/1998']
        ]);
    }
}