
<x-header />
<?php foreach($blogs as $blog): ?>
    <a href="<?= route('blog-post', ['slug' => $blog->slug]) ?>"><?= $blog->title ?></a>
<?php endforeach; ?>
<hr/>
<h3>Add New Post</h3>

<?php if (\Swidly\Core\Store::hasKey('success')): ?>
    <div class="alert alert-success">
        <?= \Swidly\Core\Store::flashMessage('success') ?>
    </div>
<?php endif; ?>
<?php if (\Swidly\Core\Store::hasKey('error')): ?>
    <div class="alert alert-danger">
        <?= \Swidly\Core\Store::flashMessage('error') ?>
    </div>
<?php endif; ?>
<form method="POST" action="blog/post/create">
    <input type="hidden" name="csrf" value="<?= \Swidly\Core\Store::csrf() ?>" />
    <p><input type="text" name="title" placeholder="Post Title" required /></p>
    <p><textarea name="content" placeholder="Post Content" required></textarea></p>
    <p><button type="submit">Create Post</button></p>
</form>
{@include 'inc/footer'}