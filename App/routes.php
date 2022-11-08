<?php

/**
 * Created a route for the API
 */
$this->get('/api/json_data', function($req, $res) {
    $res->addData('header', 'test1');
    $res->json();
})
->name('about');

$this->get('/?:person_name', 'HomeController::Index')
->name('home');