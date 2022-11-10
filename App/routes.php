<?php

/**
 * Created a route for the API
 */

$this->get('/post/:id', 'PostController::Index')
->registerMiddleware(function($request, $response) {
    if(!$request->is_authenticated) {
        $response->setContent('You can not view this post');
        $response->content();
        exit;
    }
})
->name('post');

$this->get('/login', 'LoginController::Login')->name('login');

$this->get('/', 'HomeController::Index')
->name('home');