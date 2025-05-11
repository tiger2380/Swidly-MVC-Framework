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
                'body' => "I'm a passionate software engineer with over 5 years of experience in full-stack development. 
                      My journey in technology began with a fascination for problem-solving and has evolved into 
                      a deep love for creating elegant, efficient solutions.

                      Specializing in PHP and modern JavaScript frameworks, I've contributed to various open-source 
                      projects and helped build scalable applications that serve millions of users. When I'm not 
                      coding, you can find me exploring new technologies, mentoring junior developers, or writing 
                      technical articles on my blog.

                      I believe in clean code, test-driven development, and continuous learning. Currently, I'm 
                      focused on cloud architecture and microservices design patterns, always striving to stay 
                      ahead of industry trends.

                      Outside of tech, I'm an avid hiker and amateur photographer, finding inspiration in nature 
                      for both my personal and professional growth."
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