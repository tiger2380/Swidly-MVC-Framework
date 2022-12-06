<?php
    namespace Swidly\Controllers;

    use Swidly\Core\Controller;
    use Swidly\Core\Attributes\Route;

    class LoginController extends Controller {
        #[Route('GET', '/login')]
        function Login($request, $response) {
            \Swidly\Core\Store::save(\Swidly\Core\Swidly::getConfig('session_name'), 'test@test.com');

            $response->setContent('You have been logged in.')->content();
        }

        #[Route('GET', '/logout')]
        function Logout($request, $response) {
            \Swidly\Core\Store::delete(\Swidly\Core\Swidly::getConfig('session_name'));

            $response->redirect('/');
        }
    }