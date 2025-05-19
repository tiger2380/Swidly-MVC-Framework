<?php use Swidly\Core\Helpers\UrlHelper; ?>
<?php foreach($blogs as $blog): ?>
    <a href="<?= UrlHelper::getPermalink($blog->getSlug(), [], 'https://thaddeusbibbs.me/dev/mvc-skeleton-1/') ?>"><?= $blog->getTitle() ?></a>
<?php endforeach; ?>