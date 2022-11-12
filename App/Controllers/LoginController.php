<?php
    namespace App\Controllers;

    use App\Core\Controller;
    use App\Core\Attributes\Route;

    class LoginController extends Controller {
        #[Route('GET', '/login')]
        function Login($request, $response) {
            \App\Core\Store::save(\App\Core\App::getConfig('session_name'), 'test@test.com');

            $response->setContent('You have been logged in.')->content();
        }
    }