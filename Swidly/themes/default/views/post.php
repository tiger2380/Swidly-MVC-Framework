<a href="<?= $blog->getSlug() ?>/edit">Edit post</a>
<?= \Swidly\Core\Store::flashMessage('success') ?>
<h1><?= $blog->getTitle() ?></h1>
<p><?= $blog->getContent() ?></p>
<small><?= $blog->getCreatedAt() ?></small>