<?php

declare(strict_types=1);

namespace Swidly\themes\default\controllers;

use Swidly\Core\Attributes\Middleware;
use Swidly\Core\Controller;
use Swidly\Core\Attributes\Route;
use Swidly\Core\SwidlyException;
use Swidly\Middleware\CsrfMiddleware;

class HomeController extends Controller {

    /**
     * @throws SwidlyException
     */
    #[Route(methods: ['GET'], path: ['/', '/home'])]
    function Index($req, $res) {
        $this->render('home', 
        [
            'name' => 'world',
            'data' => ['age' => 24, 'sex' => 'male', 'birthday' => '01/25/1998']
        ]);
    }

    /**
     * @throws SwidlyException
     */
    #[Route(methods: 'GET', path: '/about')]
    function About($req, $res) {
        $model = $this->getModel('PostModel');
        $this->render('about', ['title' => 'About Page']);
    }

    /**
     * @throws SwidlyException
     */
    #[Middleware((CsrfMiddleware::class))]
    #[Route(methods: ['GET', 'POST'], path: '/contact')]
    function Contact($req, $res) {
        $this->render('contact', ['title' => 'Contact Page']);
    }
}