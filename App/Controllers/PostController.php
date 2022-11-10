<?php
    namespace App\Controllers;

    use App\Core\Controller;

    class PostController extends Controller {
        function Index($req, $res) {
            $postId = $req->get('id');
            $email = \App\Core\Store::get(\App\Core\App::getConfig('session_name'));
            $this->render('post', ['email' => $email, 'postId' => $postId]);
        }
    }