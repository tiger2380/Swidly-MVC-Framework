<x-header />
<h1>Edit Post</h1>
<?php if (\Swidly\Core\Store::hasKey('success')): ?>
    <div class="alert alert-success"><?= \Swidly\Core\Store::flashMessage('success') ?></div>
<?php endif; ?>
<form method="POST" action="">
    <input type="hidden" name="csrf" value="<?= \Swidly\Core\Store::csrf() ?>" />
    <input type="hidden" name="slug" value="<?= $blog->slug ?>" />
    <input type="text" name="title" value="<?= $blog->title ?>" />
    <textarea name="content"><?= $blog->content ?></textarea>
    <button type="submit">Save</button>
</form>
<x-footer />