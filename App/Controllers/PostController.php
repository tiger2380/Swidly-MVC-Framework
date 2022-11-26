<?php
    namespace App\Controllers;

use App\Core\Attributes\Middleware;
use App\Core\Controller;
    use App\Models\PostModel;
    use App\Core\Attributes\Route;
use App\Middleware\AuthMiddleware;

    class PostController extends Controller {

        #[Route('GET', '/posts')]
        function Index($req, $res) {
            $posts = $this->model->findAll();
            $this->render('post', ['posts' => $posts]);
        }

        #[Route(methods: ['POST'], path: '/posts/add', name: 'addPost')]
        function AddPost($req, $res) {
            $post = new PostModel();
            $post->setTitle($req->get('title'));
            $post->setBody($req->get('content'));
            $post->setCreatedAt('2022-11-11 02:46:00');

            $post->save();
        }

        #[Middleware(AuthMiddleware::class)]
        #[Route(methods: ['GET'], path: '/post/:id', name: 'viewPost')]
        function ViewSingle($req, $res) {
            $id = $req->get('id');
            $post = $this->model->find(['id' => $id]);

            $this->render('single', ['post' => $post, 'title' => $post->getTitle()]);
        }

        #[Route(methods: ['POST'], path: '/posts/update', name: 'updatePost')]
        function UpdatePost($req, $res) {
            $postModel = $this->model->find(['id' => 2]);
            $postModel->setTitle($req->get('title'));

            $postModel->save();
        }
    }