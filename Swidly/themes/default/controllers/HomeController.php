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
    private View $view;
    public function __construct() {
        $this->view = new View();
        $this->view->registerCommonComponents();
    }

    /**
     * @throws SwidlyException
     */
    #[Route(methods: ['GET'], path: ['/', '/home'], name: 'home')]
    function Index($req, $res) {
        $this->view->component('button', \Swidly\themes\default\components\Button::class);
        
        return $this->view->render('home', [
            'title' => 'Home',
            'message' => 'Hello, World!'
        ]);
    }

    /**
     * @throws SwidlyException
     */
    #[Route(methods: ['GET'], path: '/about', name: 'about')]
    function About($req, $res) {
        return $this->view->render('about', 
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
        return $this->view->render('contact');
    }
}