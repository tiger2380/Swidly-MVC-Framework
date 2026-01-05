<x-header />
<h1>Welcome {{ $title }}</h1>
<p>components</p>

<?php for ($i = 0; $i < 3; $i++): ?>
    <x-button type="success" onclick="alert('Button <?php echo $i + 1; ?> clicked!')">
        button <?php echo $i + 1; ?>
    </x-button>
<?php endfor; ?>