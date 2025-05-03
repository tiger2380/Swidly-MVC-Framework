<<<<<<< HEAD
<?php
namespace Swidly\themes\default\controllers;

use Swidly\Core\Attributes\Middleware;
use Swidly\Core\Factory\CommandFactory;

use Swidly\Core\Controller;
use Swidly\Core\Attributes\Route;
use Swidly\Core\Swidly;
use Swidly\Core\SwidlyException;
use Swidly\Middleware\CsrfMiddleware;

/**
 * @throws SwidlyException
 */

class PostController extends Controller {
    #[Route(methods: ['GET'], path: '/post')]
    public function Index($req, $res) {
        echo 'This is Post controller.';
    }
}
=======
<?php
<<<<<<< HEAD:Swidly/themes/single_page/controllers/PostController.php
    namespace Swidly\themes\single_page\controllers;
=======
    namespace Swidly\themes\default\controllers;
>>>>>>> 264e7cc21600ddd025ea82dfa9ff19115d813106:Swidly/themes/default/controllers/PostController.php

    use Swidly\Core\Attributes\Middleware;
    use Swidly\Core\Controller;
    use Swidly\Core\Attributes\Route;
    use Swidly\Middleware\AuthMiddleware;

    class PostController extends Controller {

        #[Route('GET', '/posts')]
        function Index($req, $res) {
            $model = $this->getModel('PostModel');
            $posts = $model->findAll();

            usort($posts, function ($item1, $item2) {
                return $item2->getId() <=> $item1->getId();
            });

            $this->render('post', ['posts' => $posts, 'title' => 'Posts']);
        }

        #[Route(methods: ['POST'], path: '/posts/add', name: 'addPost')]
        function AddPost($req, $res) {
            $post = $this->getModel('PostModel');
            $post->setTitle($req->get('title'));
            $post->setBody($req->get('content'));
            $date = date('Y-m-d H:i:s');
            $post->setCreatedAt($date);

            if($post->save()) {
                $res->addData('message', 'Saved Successfully');
                $res->addData('status', true);
            } else {
                $res->addData('message', 'Unable to save data');
                $res->addData('status', false);
            }
            
            $res->json();
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
>>>>>>> 264e7cc21600ddd025ea82dfa9ff19115d813106
