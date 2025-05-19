<form method="POST" action="">
    <input type="hidden" name="csrf" value="<?= \Swidly\Core\Store::csrf() ?>" />
    <input type="hidden" name="slug" value="<?= $blog->getSlug() ?>" />
    <input type="text" name="title" value="<?= $blog->getTitle() ?>" />
    <textarea name="content"><?= $blog->getContent() ?></textarea>
    <button type="submit">Save</button>
</form>