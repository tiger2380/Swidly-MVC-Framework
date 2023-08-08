<?php

/**
 * Created a route for the API
 */

use Swidly\Middleware\AuthMiddleware;

$this->get('/sw_admin/themes', function($req, $res) {
    $documentRoot = $_SERVER['DOCUMENT_ROOT'];
    $themesPath = $documentRoot.'/Swidly/themes';
    $themeInfos = [];

    $themes = array_diff(scandir($themesPath), ['..', '.']);
    foreach ($themes as $theme) {
        $realPath = $themesPath.'/'.$theme;
        if(is_dir($realPath) && $theme !== 'sw_admin') {
            $themeFile = glob($realPath.'/theme.*')[0];
           $themeInfos[] = \Swidly\Core\File::readArray($themeFile);
        }
    }

    $res->addData('themes', $themeInfos);
    $res->json();
})->registerMiddleware(AuthMiddleware::class);