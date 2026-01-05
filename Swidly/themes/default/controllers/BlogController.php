<?php
namespace Swidly\themes\default\controllers;

use Swidly\Core\View;
use Swidly\Core\Model;

use Swidly\Core\Swidly;
use Swidly\Core\Controller;
use Swidly\Core\SwidlyException;
use Swidly\Core\Attributes\Route;
use Swidly\Middleware\CsrfMiddleware;
use Swidly\Core\Attributes\Middleware;
use Swidly\Core\Attributes\RouteGroup;
use Swidly\Core\Factory\CommandFactory;
use Swidly\Middleware\AuthMiddleware;

/**
 * @throws SwidlyException
 */

 #[RouteGroup(prefix: 'blog')]
class BlogController extends Controller {
    #[Route(methods: ['GET'], path: '/', name: 'index')]
    public function Index($req, $res) {
        $model = Model::load('BlogModel');

        $blogs = $model->findAll();

        return $this->render('blog', [
            'blogs' => $blogs
        ]);
    }

    #[Route(methods: ['GET'], path: '/:slug', name: 'post')]
    public function Post($req, $res) {
        $model = Model::load('BlogModel');
        $blog = $model->find(['slug' => $req->get('slug')]);
        if (!$blog) {
            return $res->redirect('/blog');
        }
        return $this->render('post', [
            'blog' => $blog
        ]);
    }

    #[Route(methods: ['GET', 'POST'], path: '/:slug/edit', name: 'edit')]
    public function Edit($req, $res) {
        $model = Model::load('BlogModel');
        /**
         * @var \Swidly\themes\default\models\BlogModel $blog
         */
        $blog = $model->find(['slug' => $req->get('slug')]);
        if (!$blog) {
            return $res->redirect('/blog');
        }

        if ($req->isPost()) {
            $data = $req->getBody();
            $blog->title = ($data['title']);
            $blog->content = $data['content'];
            $blog->save();

            \Swidly\Core\Store::save('success', 'Post updated successfully');
            return $res->redirect('blog/'.$data['slug']);
        }

        return $this->render('edit_post', [
            'blog' => $blog
        ]);
    }

    #[Route(methods: ['POST'], path: '/post/create')]
    public function Create($req, $res) {
        /**
         * @var \Swidly\themes\default\models\BlogModel $blog
         */
        $blog = Model::load('BlogModel');

        if ($req->isPost()) {
            $data = $req->getBody();
            $blog->title = $data['title'];
            $blog->content = $data['content'];
            $blog->slug = $data['slug'] ?? slugify($data['title']);
            $blog->createdAt = date('Y-m-d H:i:s');
            $blog->updatedAt = date('Y-m-d H:i:s');
            $blog->userId = AuthMiddleware::getUserId() ?? -1;
            $blog->categoryId = 1;
            $blog->status = 1;
            $blog->views = 0;
            $blog->likes = 0;
            $blog->dislikes = 0;
            $blog->comments = null;
            $blog->shares = 0;
            $blog->save();

            \Swidly\Core\Store::save('success', 'Post created successfully');
            return $res->redirect('blog/'.$blog->slug);
        }

        return $this->render('edit_post', [
            'blog' => $blog
        ]);
    }
}