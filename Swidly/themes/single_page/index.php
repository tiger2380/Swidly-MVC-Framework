<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= Swidly\Core\Swidly::getConfig('app::title') ?></title>
</head>
<body>
    <a href="/" data-sp-link>Home</a> | <a href="/posts" data-sp-link>Posts</a> | <a href="/about" data-sp-link>About</a> | <a href="/contact" data-sp-link>Contact</a> | 

    <div id="app"></div>
     
    <?= \Swidly\Core\Swidly::load_single_page(); ?>
</body>
</html>