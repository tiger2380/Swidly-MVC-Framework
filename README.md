# Welcome to the smallest and simplest PHP MVC Framework

This is a small, simple yet powerful PHP MVC Framework built with PHP. No installation/composer require. Oh yeah, it's free!! 😊

1. First, download the framework by downloading the zip or cloning the repo.
1. Run composer... oh right, no composer requires!
1. That's it. To configure, Open [App/Core/Config.php](App/Core/Config.php) and fill out your server information
1. Create routes, add controllers, views, models.
1. Also supported, templating and multi-language. You can edit the file at: [App/lang/en.json](App/lang/en.json)

## Configuration
The configuration settings are stored under [App/Core/Config.php](App/Core/Config.php)
You can access the settings in your code by: `App::getConfig('db::host')`. You can also set a default value by: `App::getConfig('db::host', 'localhost')`.

## Routing

The [Router](App/Core/Router.php) translates URLs into controllers and actions. Routes are added in the [front controller](public/index.php). A sample home route is included that routes to the `index` action in the [Home controller](App/Controllers/HomeController.php).

Routes are location in the [App/routes.php](App/routes.php) file

To add a route, you can do it like so: `$this->get('{path}', '{controller}::{action}')` method. 

```php
// Give a name to your route so you can access it later in your controller
$this->get('', 'HomeController::Index')->name('home');
$this->post('posts/blog', 'BlogController::AddPost');
```

You can also created a route without a controller:
```php
$this->get('/post/:id', function($request, $response) {
    $id = $request->get('id');

    echo 'The post id is: '. $id;
});

// or a optional paramter
$this->get('/post/?:id', function($request, $response) {
    $id = $request->get('id', '0');

    echo 'The post id is: '. $id;
});
```

Add a middleware with your request.

A middleware is an action that can occur before the request is sent to the controller. You can validate a request, change a parameter in a request or make sure  a user is logged in before continue a request.
```php
$this->get('/post/:id', function($request, $response) {
    $id = $request->get('id');

    // Now if the Id is greater than 5, it will be set to 5
    echo 'The post id is: '. $id;
})->registerMiddleware(function($request, $response) {
    $id = (int) $request->get('id');

    if($id > 5) {
        $request->set('id', '5');
    }
});
```

Validate if a user is logged on.
```php
$this->get('/post/:id', function($request, $response) {
    $id = $request->get('id');

    // Now if the Id is greater than 5, it will be set to 5
    echo 'The post id is: '. $id.'. Welcome '. $request->get('user_username');

})->registerMiddleware(function($request, $response) {
    if(!$request->is_authenticated) {
        $response->setContent('You must be logged on to see this page');

        $response->content();
        exit;
    } else {
        $user = $request->getUser();

        $request->set('user_username', $user->username);
    }
});
```

## Middlewares
Middlewares can be stored in the [App/Middleware](App/Middleware) directory

## Controllers
Controllers respond to user actions (clicking on a link, submitting a form etc.). Controllers are classes that extend the [App\Core\Controller](App/Core/Controller.php) class.

Controllers are stored in the `App/Controllers` folder. A sample [Home controller](App/Controllers/HomeController.php) included. Controller classes need to be in the `App/Controllers` namespace. You can add subdirectories to organise your controllers, so when adding a route for these controllers you need to specify the namespace (see the routing section above).

Controller classes contain methods that are the actions. To create an action, add the **`Action`** suffix to the method name. The sample controller in [App/Controllers/HomeController.php](App/Controllers/HomeController.php) has a sample `index` action.

You can access route parameters (for example the **id** parameter shown in the route examples above) in actions via the `$request->get('id')` property.


## Views

Views are used to display information (normally HTML). View files go in the `App/themes/{themename}/views/` folder. Views can be in one of two formats: standard PHP, but with just enough PHP to show the data. No database access or anything like that should occur in a view file. You can render a standard PHP view in a controller, optionally passing in variables, like this:

```php
$this->render('home', [
    'name'    => 'Dave',
    'data' => ['age' => 24, 'sex' => 'male', 'birthday' => '01/25/1998']
]);
```

You can also use the render function in views

## Models
Models are used to get and store data in your application. They know nothing about how this data is to be presented in the views. Models extend the `App\Core\Model` class and use [PDO](http://php.net/manual/en/book.pdo.php) to access the database. They're stored in the `App/Models` folder. A sample post model class is included in [App/Models/PostModel.php](App/Models/PostModel.php). 

You must set the `$table` property of the model. The `$idField` is optional.

You can then get data from the database in the controller like so:
```php
$this->model->findAll();
```

This is example how to insert new data into the database
```php
\App\Core\DB::Table('posts')->Insert(['post_title' => 'post title', 'poster_id' => 123, 'post_body' => 'this is an example post']);
```