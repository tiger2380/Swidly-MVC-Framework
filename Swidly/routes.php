<?php

/**
 * Created a route for the API
 */

use Swidly\Middleware\AuthMiddleware;

$this->get('/themes/get', function($req, $res) {
    $documentRoot = APP_PATH;
    $themesPath = $documentRoot.'/themes';
    $themeInfos = [];

    $themes = array_diff(scandir($themesPath), ['..', '.']);
    foreach ($themes as $theme) {
        $realPath = $themesPath.'/'.$theme;
        if(is_dir($realPath) && $theme !== 'sw_admin') {
            $themeFile = glob($realPath.'/theme.*')[0];
           $themeInfos[] = \Swidly\Core\File::readArray($themeFile);
        }
    }

    dump($themeInfos);
});

$this->get('/about/?:id', function($req, $res) {
    self::render('about');
});

$this->group(['prefix' => 'api', 'before' => 'changeName'], function($router) {
    $router->get('/setName/:name', function($req, $res) {
        $name = $req->get('name');

        echo 'Hello '. $name;
    });
});