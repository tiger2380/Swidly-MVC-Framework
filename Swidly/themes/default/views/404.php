{@include 'inc/header'}
<div id="notfound" class="py-5">
    <div class="notfound">
        <div class="notfound-404">
            <h1><?= $code ?></h1>
        </div>
        <?php if ($code == 404): ?>
            <h2>We are sorry, Page not found!</h2>
            <p>The page you are looking for might have been removed had its name changed or is temporarily unavailable.</p>
        <?php endif; ?>
        <div class="my-4"><strong><?= $message ?></strong></div>
        <a href="/">Back To Homepage</a>
    </div>
</div>
{@include 'inc/footer'}