<x-header />
<a href="<?= route('blog-edit', ['slug' => $blog->slug]) ?>">Edit post</a>
<?= \Swidly\Core\Store::flashMessage('success') ?>
<h1><?= $blog->getTitle() ?></h1>
<p><pre><?= parseLinks($blog->content) ?></pre></p>
<small><?= $blog->getCreatedAt() ?></small>
<x-footer />