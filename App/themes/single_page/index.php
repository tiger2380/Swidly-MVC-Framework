<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Single Page Theme</title>
</head>
<body>
    <a href="/posts" data-sp-link>Posts</a>
    <div id="app"></div>

    <?= \App\Core\App::load_single_page(); ?>
</body>
</html>