<?php

declare(strict_types=1);

namespace Swidly\themes\default\controllers;

use Swidly\Core\Attributes\Middleware;
use Swidly\Core\Controller;
use Swidly\Core\Attributes\Route;
use Swidly\Core\Swidly;
use Swidly\Core\SwidlyException;
use Swidly\Middleware\CsrfMiddleware;

class HomeController extends Controller {

    /**
     * @throws SwidlyException
     */
    #[Route(methods: ['GET'], path: '/', name: 'home')]
    function Index($req, $res) {
        $this->render('home', 
        [
            'data' => ['title' => 'Home'],
        ]);
    }

    /**
     * @throws SwidlyException
     */
    #[Route(methods: ['GET'], path: '/about/?:id/?:num', name: 'about')]
    function About($req, $res) {
        $num = (int)$req->get('num', 2);
        $word = $req->get('id');

        $this->render('about', 
        [
            'data' => ['id' => $req->get('id')],
            'words' => array_fill(0, $num, $word)
        ]);
    }
}