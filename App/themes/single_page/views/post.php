<h1>This is the post page</h1>
<h4>Only authorized users can view this.</h4>

<form method="post" action="<?= App\Core\App::path('addPost') ?>">
    <legend>Title</legend>
    <input type="text" name="title" />

    <legend>Content</legend>
    <textarea name="content"></textarea>
    <br/>
    <button type="submit">Add Post</button>
</form>
<br/><br/>
<?php foreach ($posts as $post): ?>
<div class="post">
    <h2><a href="<?= App\Core\App::path('viewPost', ['id' => $post->id]) ?>" data-sp-link><?= $post->getTitle() ?></a></h2>
    <small>Posted on: <?= $post->getCreatedAt() ?></small>
    <p><?= $post->getBody() ?></p>
</div>
<?php endforeach; ?>