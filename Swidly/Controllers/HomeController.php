<?php

declare(strict_types=1);

namespace Swidly\Controllers;

use Swidly\Core\Controller;
use Swidly\Core\Attributes\Route;

class HomeController extends Controller {

    #[Route(methods: ['GET'], path: ['/', '/home'])]
    function Index($req, $res) {
        $this->render('home', 
        [
            'name' => 'world',
            'data' => ['age' => 24, 'sex' => 'male', 'birthday' => '01/25/1998']
        ]);
    }

    #[Route(methods: 'GET', path: '/about')]
    function About($req, $res) {
        $this->render('about', ['title' => 'About Page']);
    }

    #[Route(methods: 'GET', path: '/contact')]
    function Contact($req, $res) {
        $this->render('contact', ['title' => 'Contact Page']);
    }
}