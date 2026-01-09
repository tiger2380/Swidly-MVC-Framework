<?php

declare(strict_types=1);

namespace Swidly\themes\default\controllers;

use Swidly\Core\Attributes\Middleware;
use Swidly\Core\Controller;
use Swidly\Core\Attributes\Route;
use Swidly\Core\Swidly;
use Swidly\Core\SwidlyException;
use Swidly\Core\View;
use Swidly\Middleware\CsrfMiddleware;

class HomeController extends Controller {
    /**
     * @throws SwidlyException
     */
    #[Route(methods: ['GET'], path: ['/', '/home'], name: 'home')]
    function Index($req, $res) {
        $this->view->component('button', \Swidly\themes\default\components\Button::class);
        
        return $this->render('home', [
            'title' => 'Home',
            'message' => 'Hello, World!'
        ]);
    }

    /**
     * @throws SwidlyException
     */
    #[Route(methods: ['GET'], path: '/about', name: 'about')]
    function About($req, $res) {
        $data = [
            'first_name' => 'Thaddeus',
            'last_name' => 'Bibbs',
            'dob' => 'January 1, 1990',
            'body' => 'I am a software developer with a passion for creating web applications using modern frameworks and technologies. In my free time, I enjoy hiking, reading sci-fi novels, and experimenting with new programming languages.'
        ];

        return $this->render('about', $data);
    }

    /**
     * @throws SwidlyException
     */
    #[Route(methods: ['GET', 'POST'], path: '/contact', name: 'contact')]
    function Contact($req, $res) {
        if ($req->isPost()) {
            $req->validate([
                'first_name' => 'required|string',
                'last_name' => 'required|string',
                'email' => 'required|email',
                'message' => 'required|string'
            ]);
            
            $firstName = $req->get('first_name');
            
            $res->addMessage('success', 'Hi, '. $firstName .'. Your message has been submitted');
            $res->redirect('contact');
        }
        return $this->render('contact');
    }
}