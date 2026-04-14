<?php
namespace Swidly\themes\default\controllers;

use Swidly\Core\Swidly;
use Swidly\Core\Controller;
use Swidly\Core\SwidlyException;
use Swidly\Core\Attributes\Route;
use Swidly\Core\Attributes\RouteGroup;
use Swidly\Middleware\AuthMiddleware;
use Swidly\Core\FlatDB; 

/**
 * @throws SwidlyException
 */

 #[RouteGroup(prefix: 'blog')]
class BlogController extends Controller {
    #[Route(methods: ['GET'], path: '/', name: 'index')]
    public function Index($req, $res) {
        /*$model = Model::load('BlogModel');

        $blogs = $model->findAll();*/

        $db = new FlatDB(Swidly::getBasePath() . '/data');
        $blogs = $db->find('blogs');
        $blogs = array_map(fn($blog) => (object)$blog, $blogs);

        return $this->render('blog', [
            'blogs' => $blogs
        ]);
    }

    #[Route(methods: ['GET'], path: '/:slug', name: 'post')]
    public function Post($req, $res) {
        $db = new FlatDB(Swidly::getBasePath() . '/data');
        $blog = $db->findOne('blogs', ['slug' => $req->get('slug')]);
        if (!$blog) {
            return $res->redirect('/blog');
        }
        return $this->render('post', [
            'blog' => (object)$blog
        ]);
    }

    #[Route(methods: ['GET', 'POST'], path: '/:slug/edit', name: 'edit')]
    public function Edit($req, $res) {
        $db = new FlatDB(Swidly::getBasePath() . '/data');
        $blog = $db->findOne('blogs', ['slug' => $req->get('slug')]);

        if (count((array)$blog) === 0) {
            return $res->redirect('/blog');
        }

        if ($req->isPost()) {
            $data = $req->getBody();

            $db->update('blogs', ['slug' => $req->get('slug')], [
                'title' => $data['title'],
                'content' => $data['content'],
                'updatedAt' => date('Y-m-d H:i:s')
            ]);


            \Swidly\Core\Store::save('success', 'Post updated successfully');
            return $res->redirect('blog/'.$data['slug']);
        }

        return $this->render('edit_post', [
            'blog' => $blog
        ]);
    }

    #[Route(methods: ['POST'], path: '/post/create')]
    public function Create($req, $res) {
        $db = new FlatDB(Swidly::getBasePath() . '/data');

        if ($req->isPost()) {
            $data = $req->getBody();
            $blog = $db->insert('blogs', [
                'title' => $data['title'],
                'content' => $data['content'],
                'slug' => slugify($data['title']),
                'createdAt' => date('Y-m-d H:i:s'),
                'updatedAt' => date('Y-m-d H:i:s'),
                'userId' => AuthMiddleware::getUserId() ?? -1,
                'categoryId' => 1,
                'status' => 1,
                'views' => 0,
                'likes' => 0,
                'dislikes' => 0,
                'comments' => null,
                'shares' => 0
            ]);

            \Swidly\Core\Store::save('success', 'Post created successfully');
            //return $res->redirect('blog/'.$blog->slug);
            return $res->redirect('blog'.'/'.$blog['slug']);
        }
    }
}