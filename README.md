# Welcome to 'Not Another' PHP MVC Framework

This is a small, simple yet powerful PHP MVC Framework built with vanilla PHP using no frameworks. My goal is to make a self-contain MVC that doesn't depend on any 3rd parties. No installation/composer require. I'm trying my best to lower the learning curve when it comes to MVC. 

Steps to implement this MVC on your server:

1. First, get the code by downloading the zip or cloning the repo.
1. Run composer... oh right, no composer installation is required!
1. That's it. To configure, Open [Swidly/Core/Config.php](Swidly/Core/Config.php) and fill out your server information
1. Create routes, controllers, views, and models.

Also supports, templating and multi-language. You can edit the file at: [Swidly/lang/en.json](Swidly/lang/en.json). Wrap the word/phrase with {} and the templating system will handle the rest. 

You can start the app by using php built-in server by typing:
```terminal
php -S localhost:8000
```
and then avigate to `localhost:8000` in your browser.

## Configuration
The configuration settings are stored under [Swidly/Core/Config.php](Swidly/Core/Config.php)
You can access the settings in your code by: `Swidly::getConfig('db::host')`. You can also set a default value by: `Swidly::getConfig('db::host', 'localhost')`.

## Routing

The [Router](Swidly/Core/Router.php) translates URLs into controllers and actions. Routes are added to the [front controller](public/index.php). A sample home route is included that routes to the `index` action in the [Home controller](Swidly/Controllers/HomeController.php).

Routes are located in the [Swidly/routes.php](Swidly/routes.php) file

To add a route, you can do it like so: `$this->get('{path}', '{controller}::{action}')` method. 

```php
// Give a name to your route so you can access it later in your controller
$this->get('', 'HomeController::Index')->name('home');
$this->post('posts/blog', 'BlogController::AddPost');
```

You can also create a route without a controller:
```php
$this->get('/post/:id', function($request, $response) {
    $id = $request->get('id');

    echo 'The post id is: '. $id;
});

// or a optional parameter
$this->get('/post/?:id', function($request, $response) {
    $id = $request->get('id', '0');

    echo 'The post id is: '. $id;
});
```

Add a middleware with your request.

Middleware is an action that can occur before the request is sent to the controller. You can validate a request, change a parameter in a request or make sure a user is logged in before continuing a request.
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

Routes can also be created in a controller using attributes.
You can also add middleware to your routes.
`#[Route('{method]}', '{path}', '{route name}optional')]`
```php
class PostController extends Controller {

    #[Route('GET', '/posts')]
    #[Middleware(AuthMiddleware::class)]
    function Index($req, $res) {
        $posts = $this->model->findAll();
        $this->render('post', ['posts' => $posts]);
    }

    ....
}
```

## Middlewares
Middlewares can be stored in the [Swidly/Middleware](Swidly/Middleware) directory

## Controllers
Controllers respond to user actions (clicking on a link, submitting a form etc.). Controllers are classes that extend the [Swidly\Core\Controller](Swidly/Core/Controller.php) class.

Controllers are stored in the `Swidly/Controllers` folder. A sample [Home controller](Swidly/Controllers/HomeController.php) is included. Controller classes need to be in the `Swidly/Controllers` namespace. You can add subdirectories to organize your controllers, so when adding a route for these controllers you need to specify the namespace (see the routing section above).

Controller classes contain methods that are the actions. To create an action, add the **`Action`** suffix to the method name. The sample controller in [Swidly/Controllers/HomeController.php](Swidly/Controllers/HomeController.php) has a sample `index` action.

You can access route parameters (for example the **id** parameter shown in the route examples above) in actions via the `$request->get('id')` property.


## Views

Views are used to display information (normally HTML). View files go in the `Swidly/themes/{themename}/views/` folder. Views can be in one of two formats: standard PHP, but with just enough PHP to show the data. No database access or anything like that should occur in a view file. You can render a standard PHP view in a controller, optionally passing in variables, like this:

```php
$this->render('home', [
    'name'    => 'Dave',
    'data' => ['age' => 24, 'sex' => 'male', 'birthday' => '01/25/1998']
]);
```

You can also use the render function in views

## Models
Models are used to get and store data in your application. They know nothing about how this data is to be presented in the views. Models extend the `Swidly\Core\Model` class and use [PDO](http://php.net/manual/en/book.pdo.php) to access the database. They're stored in the `Swidly/Models` folder. A sample post model class is included in [Swidly/Models/PostModel.php](Swidly/Models/PostModel.php). 

You must set the `$table` property of the model. The `$idField` is also required.

In the modal, you have to tell the modal the what is an column in the table by using the `Column` attribute
```php
#[Column()]
private ?string $title = null;
```

The Id column is require
```php
#[Column()]
public int $id;
```

There must be a getter/setter for that column in your model:
```php
public function getTitle(): string {
    return $this->title;
}

public function setTitle(string $title): self {
    $this->title = $title;

    return $this;
}
```

You can then get data from the database in the controller like so:
```php
$this->model->findAll();
//or
$this->model->findBy(['id' => '123']);
```

This is example how to insert new data into the database
```php
\Swidly\Core\DB::Table('posts')->Insert(['post_title' => 'post title', 'poster_id' => 123, 'post_body' => 'this is an example post']);
```

Insert data using an entity for example `PostModel`.
```php
#[Route('POST', '/posts/add', 'addPost')]
function AddPost($req, $res) {
    $post = new PostModel();
    $post->setTitle($req->get('title'));
    $post->setBody($req->get('content'));
    $post->setCreatedAt('2022-11-11 02:46:00');

    $post->save();
}
```

Update data
```php
#[Route(methods: ['POST'], path: '/posts/update', name: 'updatePost')]
function UpdatePost($req, $res) {
    $postModel = $this->model->find(['id' => 2]);
    $postModel->setTitle($req->get('title'));

    $postModel->save();
}
```

## Single Page Support (beta)
Support single page with ease right out-of-the-box

To enable single page support, just add a `<div id="app"></div>` to the index file of the theme and load the single page script: `<?= \Swidly\Core\Swidly::load_single_page(); ?>`

Please see below:
```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= Swidly\Core\Swidly::getConfig('app::title') ?></title>
</head>
<body>
    <a href="/" data-sp-link>Home</a> | <a href="/posts" data-sp-link>Posts</a> | <a href="/about" data-sp-link>About</a> | <a href="/contact" data-sp-link>Contact</a> | 

    <div id="app"></div>
     
    <?= \Swidly\Core\Swidly::load_single_page(); ?>
</body>
</html>
```

To view the demo, switch to the `single_page` theme and set `single_page` to true in the configuration file:
```php
return [
    'app' => [
        'title' =>'',
        ...
        'single_page' => true
    ],
    'default_lang' => 'en',
    'theme' => 'single_page',
    ....
]
```

Add the `data-sp-link` attribute to any buttons/links that will require navigation using the single page functionality.

That's it. No download or installation.