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
    #[Route(methods: ['GET'], path: ['/', '/home'], name: 'home')]
    function Index($req, $res) {
        $this->render('home', 
        [
            'data' => ['title' => 'Home'],
        ]);
    }

    /**
     * @throws SwidlyException
     */
    #[Route(methods: ['GET'], path: '/about', name: 'about')]
    function About($req, $res) {
        $this->render('about', 
        [
            'data' => [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'dob' => '01/01/2000',
                'body' => "this is the body of the about page"
            ],
        ]);
    }

    /**
     * @throws SwidlyException
     */
    #[Route(methods: ['GET', 'POST'], path: '/contact', name: 'contat')]
    function Contact($req, $res) {
        if ($req->isPost()) {
            $firstName = $req->get('first_name');
            
            $res->addMessage('success', 'Hi, '. $firstName .'. Your message has been submitted');
            $res->redirect('contact');
        }
        $this->render('contact');
    }
}