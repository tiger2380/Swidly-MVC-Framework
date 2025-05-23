<?php use Swidly\Core\Helpers\UrlHelper; ?>
<?php foreach($blogs as $blog): ?>
    <a href="<?= UrlHelper::getPermalink($blog->getSlug()) ?>"><?= $blog->getTitle() ?></a>
<?php endforeach; ?>