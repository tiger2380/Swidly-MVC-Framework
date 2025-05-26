{@include 'inc/header'}
<a href="<?= $blog->getSlug() ?>/edit">Edit post</a>
<?= \Swidly\Core\Store::flashMessage('success') ?>
<h1><?= $blog->getTitle() ?></h1>
<p><pre><?= \Swidly\Core\Helpers\UrlHelper::parseLinks($blog->getContent()) ?></pre></p>
<small><?= $blog->getCreatedAt() ?></small>
{@include 'inc/footer'}