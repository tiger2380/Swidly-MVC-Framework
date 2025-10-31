{@include 'inc/header'}
<h1>Welcome {title}</h1>
<p>components</p>
<x-alert type="success" role="alert">
    Your profile has been updated successfully!
</x-alert>

<?php for ($i = 0; $i < 3; $i++): ?>
    <x-button type="primary" onclick="alert('Button <?php echo $i + 1; ?> clicked!')">
        button <?php echo $i + 1; ?>
    </x-button>
<?php endfor; ?>

{@include inc/footer}