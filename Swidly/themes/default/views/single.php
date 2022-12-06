<div class="post">
    <h2><?= $post->getTitle() ?></h2>
    <small>Posted on: <?= $post->getCreatedAt() ?></small>
    <p><?= $post->getBody() ?></p>
</div>