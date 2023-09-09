<?= $this->render('inc/header') ?>
<h1>Your age is {age, default=99}</h1>
<div>
    <p><?= $data['age'] ?></p>
    <p><?= $data['sex'] ?></p>
    <p><?= $data['birthday'] ?></p>
</div>
<?= $this->render('inc/footer') ?>