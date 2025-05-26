
<?php
use \Swidly\Core\Helpers\UrlHelper;
?>
<!DOCTYPE html>
<html data-bs-theme="light" lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title><?= \Swidly\Core\Swidly::getTitle(); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
</head>

<body>
    <div class="container">
    <nav class="navbar navbar-expand-lg bg-body-tertiary">
        <div class="container-fluid">
            <a class="navbar-brand" href="home">Navbar</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item">
                <a class="nav-link <?= \Swidly\Core\Swidly::activeLink('home') ?>" href="<?= UrlHelper::getPermalink('/') ?>">Home</a>
                </li>
                <li class="nav-item">
                <a class="nav-link <?= \Swidly\Core\Swidly::activeLink('about') ?>" href="<?= UrlHelper::getPermalink('about') ?>">About</a>
                </li>
                <li class="nav-item">
                <a class="nav-link <?= \Swidly\Core\Swidly::activeLink('blog') ?>" href="<?= UrlHelper::getPermalink('blog') ?>">Blog</a>
                </li>
                <li class="nav-item">
                <a class="nav-link <?= \Swidly\Core\Swidly::activeLink('contact') ?>" href="<?= UrlHelper::getPermalink('contact') ?>">Contact</a>
                </li>
            </ul>
            </div>
        </div>
    </nav>