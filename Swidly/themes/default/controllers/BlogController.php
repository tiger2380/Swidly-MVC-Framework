<?php
namespace Swidly\themes\default\controllers;

use Swidly\Core\Attributes\Middleware;
use Swidly\Core\Factory\CommandFactory;

use Swidly\Core\Controller;
use Swidly\Core\Attributes\Route;
use Swidly\Core\Swidly;
use Swidly\Core\SwidlyException;
use Swidly\Middleware\CsrfMiddleware;
use Swidly\Core\Model;

/**
 * @throws SwidlyException
 */

class BlogController extends Controller {
    #[Route(methods: ['GET'], path: '/blog')]
    public function Index($req, $res) {
        $model = Model::load('BlogModel');

        $blogs = $model->findAll();

        $this->render('blog', [
            'blogs' => $blogs
        ]);
    }

    #[Route(methods: ['GET'], path: 'blog/:slug')]
    public function Post($req, $res) {
        $model = Model::load('BlogModel');
        $blog = $model->find(['slug' => $req->get('slug')]);
        if (!$blog) {
            return $res->redirect('/blog');
        }
        $this->render('post', [
            'blog' => $blog
        ]);
    }

    #[Route(methods: ['GET', 'POST'], path: 'blog/:slug/edit')]
    public function Edit($req, $res) {
        $model = Model::load('BlogModel');
        $blog = $model->find(['slug' => $req->get('slug')]);
        if (!$blog) {
            return $res->redirect('/blog');
        }

        if ($req->isPost()) {
            $data = $req->getBody();
            $blog->setTitle($data['title']);
            $blog->setContent($data['content']);
            $blog->save();

            \Swidly\Core\Store::save('success', 'Post updated successfully');
            return $res->redirect('blog/'.$data['slug']);
        }

        $this->render('edit_post', [
            'blog' => $blog
        ]);
    }
}