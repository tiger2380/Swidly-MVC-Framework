<?php

/**
 * Created a route for the API
 */

use Swidly\Middleware\AuthMiddleware;

$this->get('/themes/get', function($req, $res) {
    $documentRoot = SWIDLY_ROOT;
    $themesPath = $documentRoot.'/themes';
    $themeInfos = [];

    $themes = array_diff(scandir($themesPath), ['..', '.']);
    foreach ($themes as $theme) {
        $realPath = $themesPath.'/'.$theme;
        if(is_dir($realPath) && $theme !== 'sw_admin') {
            $themeFile = glob($realPath.'/theme.*');
            if (isset($themeFile[0])) {
                $themeInfos[] = \Swidly\Core\File::readArray($themeFile[0]);
            }
        }
    }

    dump($themeInfos);
});

$this->get('/thumbnail', function($req, $res) {
    header('Content-Type: image/webp');
    $image = $req->get('i');
    $width = (int) $req->get('w');
    $height = (int) $req->get('h');

    $result = resizeImageWebP($image, $width, $height);
    readfile($result);
});

$this->filter('changeName', function($req, $res) {
    $req->set('name', 'bobby');
});

$this->group(['prefix' => 'api', 'before' => 'changeName'], function($router) {
    $router->get('/setName/:name', function($req, $res) {
        $name = $req->get('name');

        echo 'Hello '. $name;
    });
});