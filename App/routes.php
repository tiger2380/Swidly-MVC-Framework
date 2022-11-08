<?php

/**
 * Created a route for the API
 */
$this->get('/api/json_data/?:id', function($req, $res) {
    $res->setContent('<h1>The ID is '. $req->get('id') .'</h1>');
    $res->content();
})->registerMiddleware(function($req, $res){
    if ($req->get('id') >= 5) {
        $req->set('id', 5);
    }
});

$this->get('/?:person_name', 'HomeController::Index')
->name('home');