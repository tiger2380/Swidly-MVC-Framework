<?php
<<<<<<< HEAD
    namespace Swidly\themes\default\controllers;

    use Swidly\Core\Attributes\Middleware;
    use Swidly\Core\Attributes\Route;
    use Swidly\Core\Controller;
    use Swidly\Middleware\CsrfMiddleware;

    class LoginController extends Controller {
        #[Route(methods: ['GET', 'POST'], path: '/login')]
        #[Middleware(CsrfMiddleware::class)]
        function Login($request, $response) {
            if($request->isPost()) {
                $hasError = false;
                $email = $request->get('email');
                $password = $request->get('password');
                $userModel = $this->getModel('UserModel');
                if(empty($email) || empty($password)) {
                    $results = [
                        'message' => 'Email address or password field can not be empty.'
                    ];
                    $hasError = true;
                } else {
                    /** @var UserModel $user */
                    $user = $userModel->find(['email' => $email]);
                    $results = [
                        'message' => ''
                    ];

                    if (!$user) {
                        $results = [
                            'message' => 'Email address can not be found.'
                        ];
                        $hasError = true;
                    } else {
                        $password_hash = $user->getPassword();
                        $password_hash = password_verify($password, $password_hash);
                        if($password_hash) {
                            \Swidly\Core\Store::save(\Swidly\Core\Swidly::getConfig('session_name'), $email);
                            $results = ['message' => 'You have been logged in.'];
                            $hasError = false;
                        } else {
                            $results['message'] = 'Incorrect email/password.';
                            $hasError = true;
                        }
                    }
                }

                $response->addMessage('login', $results['message']);

                if ($hasError) {
                    $response->redirect('/login');
                }

                $response->redirect('/dashboard');
            } else {
                $this->render('login', ['data' => ['title' => 'Sign In']]);
            }
=======
<<<<<<< HEAD:Swidly/themes/single_page/controllers/LoginController.php
    namespace Swidly\themes\single_page\controllers;
=======
    namespace Swidly\themes\default\controllers;
>>>>>>> 264e7cc21600ddd025ea82dfa9ff19115d813106:Swidly/themes/default/controllers/LoginController.php

    use Swidly\Core\Attributes\Route;
    use Swidly\Core\Controller;

    class LoginController extends Controller {
        #[Route('GET', '/login')]
        function Login($request, $response) {
            \Swidly\Core\Store::save(\Swidly\Core\Swidly::getConfig('session_name'), 'test@test.com');

            $response->setContent('You have been logged in.')->content();
>>>>>>> 264e7cc21600ddd025ea82dfa9ff19115d813106
        }

        #[Route('GET', '/logout')]
        function Logout($request, $response) {
            \Swidly\Core\Store::delete(\Swidly\Core\Swidly::getConfig('session_name'));

            $response->redirect('/');
        }
<<<<<<< HEAD

        function ViewCode($req, $res) {
            $email = $req->get('email');
            $this->render('login-code', ['email' => $email]);
        }

        function validateCode($req, $res) {
            $email = $req->get('email');
            $code = $req->get('code');
            $results = [];

            $result = \App\DB::Table('login_tokens')->Select()->WhereOnce(['email' => $email]);

            if($result) {
                $enddate = date($result->timestamp);
                $expiry_date = new \DateTime($enddate);
                $to_date = date("Y-m-d H:i:s");
                $start_date = new \DateTime($to_date);

                if(date_diff($start_date, $expiry_date)->i > 15) {
                    $results = [
                        'status' => false,
                        'message' => 'Token has expired. A new one was sent to your email address.'
                    ];
                } else {
                    if($result->token === trim($code)) {
                        \App\DB::Table('login_tokens')->Delete()->WhereOnce(['email' => $email]);

                        \App\Store::save(\App\App::getConfig('session_name'), $email);
                        if($team = DB::Table('teams')->Select()->WhereOnce(['email' => $email])) {
                            Store::save('team_id', $team->team_id);
                        }

                        $results = [
                            'status' => true,
                            'message' => 'Please wait...'
                        ];
                    }  else {
                        $results = [
                            'status' => false,
                            'message' => 'Invalid code'
                        ];
                    }
                }
            } else {
                $results = [
                    'status' => true,
                    'message' => 'Please try again. If error persist, contact us at <email address>.'
                ];
            }

            echo json_encode($results);
        }

        /**
         * Generate and send a login code to the given email
         *
         * @param string $email The recipient's email address
         * @return int|bool The generated code or false on failure
         */
        static function generateCode(string $email): int|bool {
            // Use dependency injection instead of global
            $className = \Swidly\Core\Controller::class;
            $reflection = new \ReflectionClass($className);
            $controller = $reflection->newInstance();
            // Use cryptographically secure random number
            $code = random_int(100000, 999999);

            $loginModel = $controller->getModel('LoginTokenModel');
            $exists = $loginModel->find(['email' => $email]);

            try {
                // Use a single code path with the model
                $model = $exists ?: $loginModel;

                $model->setEmail($email);
                $model->setToken($code);
                $model->setTimestamp((new \DateTime())->format('Y-m-d H:i:s'));
                $model->save();

                // Use appropriate namespacing or import for PHPMailer
                $mail = new \Swidly\Core\Libs\PHPMailer\PHPMailer(true);

                // Use environment variables for sensitive information
                $smtpConfig = [
                    'host'     => $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com',
                    'port'     => $_ENV['SMTP_PORT'] ?? 465,
                    'security' => $_ENV['SMTP_SECURITY'] ?? 'ssl',
                    'username' => $_ENV['SMTP_USERNAME'] ?? 'tiger2380@gmail.com',
                    'password' => $_ENV['SMTP_PASSWORD'] ?? 'itJYeYXsKp8',
                    'from'     => $_ENV['SMTP_FROM'] ?? 'noreply@meebeestudio.com',
                ];


                $mail->isSMTP();
                $mail->SMTPAuth = true;
                $mail->SMTPDebug = 2;
                $mail->Host = $smtpConfig['host'];
                $mail->Port = $smtpConfig['port'];
                $mail->SMTPSecure = $smtpConfig['security'];
                $mail->Username = $smtpConfig['username'];
                $mail->Password = $smtpConfig['password'];

                $mail->From = $smtpConfig['from'];
                $mail->FromName = \Swidly\Core\Swidly::getConfig('app_title');
                $mail->addAddress($email);
                $mail->Subject = "Your " . \Swidly\Core\Swidly::getConfig('app_title') . " Login Code";

                $app_title = \Swidly\Core\Swidly::getConfig('app_title');

                // Use template file or a template engine instead of inline HTML
                $message = <<<HTML
<html>
    <body style="background-color: #eee; padding: 3rem 0;">
        <h2 style="margin: 10px auto; width: 450px; text-align: center;">{$app_title}</h2>
        <div style="padding: 2rem; background-color: white; width: 450px; height: auto; margin: 5px auto;">
            <p>Hello!</p>
            <p>Here is your <strong>{$app_title}</strong> login code:</p>
            <br/>
            <strong style="font-size: 1.2rem;">{$code}</strong>
            <br/><br/>
            <p>This code is valid for the next 15 minutes.</p>
            <p>If you did not request a login code from {$app_title}, please ignore this email.</p>
        </div>
    </body>
</html>
HTML;
                $mail->isHTML(true);
                $mail->Body = $message;

                $mail->send();
                return $code;

            } catch(\Exception $ex) {
                // Log the exception
                error_log("Error generating login code: " . $ex->getMessage());
                return false;
            }
        }
=======
>>>>>>> 264e7cc21600ddd025ea82dfa9ff19115d813106
    }