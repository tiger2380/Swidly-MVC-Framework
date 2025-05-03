<?php
<<<<<<< HEAD
<<<<<<< HEAD:Swidly/themes/single_page/controllers/LoginController.php
    namespace Swidly\themes\single_page\controllers;
=======
    namespace Swidly\themes\default\controllers;
>>>>>>> 264e7cc21600ddd025ea82dfa9ff19115d813106:Swidly/themes/default/controllers/LoginController.php
=======
    namespace Swidly\themes\single_page\controllers;
>>>>>>> 264e7cc21600ddd025ea82dfa9ff19115d813106

    use Swidly\Core\Attributes\Route;
    use Swidly\Core\Controller;

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