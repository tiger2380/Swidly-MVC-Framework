{@include 'inc/header'}
<h1>Welcome {title}</h1>
<p>components</p>
<x-alert type="success" role="alert">
    Your profile has been updated successfully!
</x-alert>

<x-alert type="error">
    There was an error processing your request.
</x-alert>
{@include inc/footer}