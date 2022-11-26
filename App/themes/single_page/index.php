<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= App\Core\App::getConfig('app::title') ?></title>
</head>
<body>
    <a href="/" data-sp-link>Home</a> | <a href="/posts" data-sp-link>Posts</a> | <a href="/about" data-sp-link>About</a> | <a href="/contact" data-sp-link>Contact</a> | 

    <?php if(App\Core\App::getConfig('app::single_page', false)): ?>
        <div id="app"></div>
        <?= \App\Core\App::load_single_page(); ?>
    <?php endif; ?>
</body>
</html>