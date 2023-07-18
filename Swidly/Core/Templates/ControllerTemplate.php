<?php

declare(strict_types=1);

namespace Swidly\themes\{THEMENAME}\controllers;

use Swidly\Core\Attributes\Route;
use Swidly\Core\Controller;
use Swidly\Core\SwidlyException;

class {CONTROLLERNAME}Controller extends Controller {
    /**
     * @throws SwidlyException
     */
    #[Route(methods: ['GET'], path: ['/'])]
    public function controller_name($req, $res) {
        $this->render('about', ['title' => 'About Page']);
    }
}