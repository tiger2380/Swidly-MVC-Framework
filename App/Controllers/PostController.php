<?php
    namespace App\Controllers;

    use App\Core\Controller;
    use App\Models\PostModel;
    use App\Core\Attributes\Route;

    class PostController extends Controller {

        #[Route('GET', '/posts')]
        function Index($req, $res) {
            $posts = $this->model->findAll();
            $this->render('post', ['posts' => $posts]);
        }

        #[Route('POST', '/posts/add', 'addPost')]
        function AddPost($req, $res) {
            $post = new PostModel();
            $post->setTitle($req->get('title'));
            $post->setBody($req->get('content'));
            $post->setCreatedAt('2022-11-11 02:46:00');

            $post->save();
        }

        #[Route('GET', '/post/:id', 'viewPost')]
        function ViewSingle($req, $res) {
            $id = $req->get('id');
            $post = $this->model->find(['id' => $id]);

            $this->render('single', ['post' => $post]);
        }

        #[Route('GET', '/postss')]
        function ExampleRoute($req, $res) {
            echo 'test';
        }
    }