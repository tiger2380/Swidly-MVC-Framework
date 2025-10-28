<?php
// In your view file (e.g., /themes/default/views/home.php)

// Using an alert component
?>
<x-alert type="success" role="alert">
    Your profile has been updated successfully!
</x-alert>

<x-alert type="error">
    There was an error processing your request.
</x-alert>

<?php
// In your controller
namespace Themes\Default\Controllers;

use Swidly\Core\Controller;

class HomeController extends Controller
{
    public function index()
    {
        $view = new \Swidly\Core\View();
        $view->registerCommonComponents();
        
        return $view->render('home', [
            'title' => 'Welcome',
            'message' => 'Hello, World!'
        ]);
    }
}
