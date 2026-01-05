# Welcome to Swidly PHP MVC Framework

This is a small, simple yet powerful PHP MVC Framework built with vanilla PHP using no frameworks. My goal is to make a self-contained MVC that doesn't depend on any 3rd parties. No installation/composer required. I'm trying my best to lower the learning curve when it comes to MVC.

## Getting Started

### Installation Steps

1. First, get the code by downloading the zip or cloning the repo.
2. Run composer... oh right, no composer installation is required!
3. That's it. To configure, open [Swidly/Core/config.php](Swidly/Core/config.php) and fill out your server information.
4. Create routes, controllers, views, and models.

Also supports templating and multi-language. You can edit the file at: [Swidly/lang/en.json](Swidly/lang/en.json). Wrap the word/phrase with `{}` and the templating system will handle the rest.

### Running the Application

You can start the app by using PHP's built-in server by typing:

```terminal
php -S localhost:8000
```

Then navigate to `localhost:8000` in your browser.

To run the sample, first create a database called `blog` and add your database information to your [Swidly/Core/config.php](Swidly/Core/config.php) file. Use the `dump.sql` to generate the test database.

> **Note:** The root `index.php` file is only needed when running from the built-in PHP server.

## Directory Structure

```plaintext
ROOT/
 ├── bin/
 │   └── console
 ├── Swidly/
 │   ├── Core/
 │   ├── lang/
 │   ├── Middleware/
 │   ├── Migrations/ (todo)
 │   └── themes/
 │       └── default/ (is where you create your MVC)
 │           └── theme.php (theme meta)
 ├── public/
 │   └── index.php
 ├── .htaccess
 └── bootstrap.php (must be in root directory)
```

## Configuration

The configuration settings are stored under [Swidly/Core/config.php](Swidly/Core/config.php). You can access the settings in your code by: `Swidly::getConfig('db::host')`. You can also set a default value by: `Swidly::getConfig('db::host', 'localhost')`.

### `getConfig(string $name, mixed $default = ''): mixed`

This static method returns the value of a configuration parameter with the given name. If the parameter is not found or is empty, it returns the default value.

**Parameters:**
- `$name` (string): The name of the configuration parameter to retrieve.
- `$default` (mixed): The default value to return if the parameter is not found or is empty. Default is an empty string.

**Return Value:**
- (mixed): The value of the configuration parameter, or the default value if the parameter is not found or is empty.

### `setConfigValue(string $name, mixed $value): void`

This method sets the value of a configuration parameter with the given name.

**Parameters:**
- `$name` (string): The name of the configuration parameter to set.
- `$value` (mixed): The value to set for the configuration parameter.

**Return Value:**
- (void): This method does not return anything.

**Throws:**
- `SwidlyException`: If the configuration file cannot be parsed.

### `setConfigValues(array $config): void`

This method sets the values of multiple configuration parameters at once.

**Parameters:**
- `$config` (array): An associative array of configuration parameter names and values.

**Return Value:**
- (void): This method does not return anything.

## Routing

The [Router](Swidly/Core/Router.php) translates URLs into controllers and actions. Routes are added to the [front controller](public/index.php). A sample home route is included that routes to the `index` action in the [Home controller](Swidly/themes/default/controllers/HomeController.php).

Routes are located in the [Swidly/routes.php](Swidly/routes.php) file.

To add a route, you can use the `$this->get('{path}', '{controller}::{action}')` method:

```php
// Give a name to your route so you can access it later in your controller
$this->get('', 'HomeController::Index')->name('home');
$this->post('posts/blog', 'BlogController::AddPost');
```

### Routes Without Controllers

You can also create a route without a controller:

```php
$this->get('/post/:id', function($request, $response) {
    $id = $request->get('id');
    echo 'The post id is: ' . $id;
});

// or an optional parameter
$this->get('/post/?:id', function($request, $response) {
    $id = $request->get('id', '0');
    echo 'The post id is: ' . $id;
});
```

### Middleware

Add middleware with your request. Middleware is an action that can occur before the request is sent to the controller. You can validate a request, change a parameter in a request, or make sure a user is logged in before continuing a request.

```php
$this->get('/post/:id', function($request, $response) {
    $id = $request->get('id');
    // Now if the Id is greater than 5, it will be set to 5
    echo 'The post id is: ' . $id;
})->registerMiddleware(function($request, $response) {
    $id = (int) $request->get('id');
    if($id > 5) {
        $request->set('id', '5');
    }
});
```

### Authentication Middleware

Validate if a user is logged in:

```php
$this->get('/post/:id', function($request, $response) {
    $id = $request->get('id');
    echo 'The post id is: ' . $id . '. Welcome ' . $request->get('user_username');
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

### Route Attributes

Routes can also be created in a controller using attributes. You can also add middleware to your routes.

**Syntax:** `#[Route('{method}', '{path}', '{route name}' (optional))]`

```php
class PostController extends Controller {
    #[Route(methods: ['GET'], path: ['/blog', '/posts'], name: 'posts')]
    #[Middleware(AuthMiddleware::class)]
    function Index($req, $res) {
        $posts = $this->model->findAll();
        $this->render('post', ['posts' => $posts]);
    }
}
```

### Route Groups

**Syntax:** `#[RouteGroup(prefix: '[name]')]`

```php
#[RouteGroup(prefix: 'blog')]
class BlogController extends Controller {
    #[Route(methods: ['GET'], path: '/')]
    public function Index($req, $res) {
        $model = Model::load('BlogModel');
        $blogs = $model->findAll();
        $this->render('blog', ['blogs' => $blogs]);
    }
}
```

All routes within this controller have a prefix `/blog`.

## Middlewares

Middlewares can be stored in the [Swidly/Middleware](Swidly/Middleware) directory.

## Controllers

Controllers respond to user actions (clicking on a link, submitting a form, etc.). Controllers are classes that extend the [Swidly\Core\Controller](Swidly/Core/Controller.php) class.

Controllers are stored in the `Swidly/Controllers` folder. A sample [Home controller](Swidly/themes/default/controllers/HomeController.php) is included. Controller classes need to be in the `Swidly/Controllers` namespace. You can add subdirectories to organize your controllers, so when adding a route for these controllers you need to specify the namespace (see the routing section above).

Controller classes contain methods that are the actions. To create an action, add the **`Action`** suffix to the method name. The sample controller in [Swidly/Controllers/HomeController.php](Swidly/Controllers/HomeController.php) has a sample `index` action.

You can access route parameters (for example the **id** parameter shown in the route examples above) in actions via the `$request->get('id')` property.

### Creating Controllers via CLI

To quickly create a controller/route, run this command in the terminal:

```terminal
php bin/console make:controller contact
```

This will generate a controller called `Contact` in the controllers directory within your selected theme (e.g., `default/`) directory:

```php
<?php
namespace Swidly\themes\default\controllers;

use Swidly\Core\Attributes\Middleware;
use Swidly\Core\Factory\CommandFactory;
use Swidly\Core\Controller;
use Swidly\Core\Attributes\Route;
use Swidly\Core\Swidly;
use Swidly\Core\SwidlyException;
use Swidly\Middleware\CsrfMiddleware;

/**
 * @throws SwidlyException
 */
class ContactController extends Controller {
    #[Route(methods: ['GET'], path: '/contact')]
    public function Index($req, $res) {
        echo 'This is Contact controller.';
    }
}
```

## Views

Views are used to display information (normally HTML). View files go in the `Swidly/themes/{themename}/views/` folder. Views can be in one of two formats: standard PHP, but with just enough PHP to show the data. No database access or anything like that should occur in a view file. You can render a standard PHP view in a controller, optionally passing in variables, like this:

```php
#[Route(methods: ['GET'], path: '/about', name: 'about')]
function About($req, $res) {
    $this->render('about', [
        'data' => [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'dob' => '01/01/2000',
            'body' => "this is the body of the about page"
        ],
    ]);
}
```

### Templating Syntax

In the view, you can get the passed parameters like so:

```php
<x-inc.header />
<h1>About me</h1>

<p>Hello, my name is <strong>{first_name} {last_name}</strong></p>
<p>I was born on <strong>{dob}</strong></p>
<p>Here is a brief summary about me: <br/> {body}</p>
<x-inc.footer />
```

**Note:** Whatever is within the curly brackets `{}` will display the data with that name. Use the curly brackets also to display different languages. For example, `{hello}` will display `hola` if the [Swidly/Core/lang/es.json](Swidly/Core/lang/es.json) has a config for `"hello": "hola"` and `'default_lang' => 'es'` is set in the `config.php` file.

### Components

Use `<x-header />` to include other views within another view as a component. This example will render the `header` component in the about page.

### View Directives

**Forms:**
- `@csrf` - CSRF token input
- `@method('PUT')` - HTTP method spoofing

**Authentication:**
- `@auth ... @endauth` - Show content only to authenticated users
- `@guest ... @endguest` - Show content only to guests

**Conditionals:**
- `@if($condition) ... @endif`
- `@elseif($condition)`
- `@else`
- `@isset($var) ... @endisset`
- `@empty($var) ... @endempty`

**Loops:**
- `@foreach($items as $item) ... @endforeach`
- `@for($i = 0; $i < 10; $i++) ... @endfor`
- `@while($condition) ... @endwhile`
- `@continue` / `@continue(2)` - Skip iteration
- `@break` / `@break(2)` - Break loop

**Utilities:**
- `@php ... @endphp` - Raw PHP blocks
- `@dd($variable)` - Dump and die
- `@dump($variable)` - Var dump
- `@json($data)` - JSON encode with pretty print

## Models

Models are used to get and store data in your application. They know nothing about how this data is to be presented in the views. Models extend the `Swidly\Core\Model` class and use [PDO](http://php.net/manual/en/book.pdo.php) to access the database. They're stored in the `Swidly/Models` folder. A sample post model class is included in [Swidly/Models/PostModel.php](Swidly/Models/PostModel.php).

You must set the `$table` property of the model. The `$idField` is also required.

### Defining Columns

In the model, you have to tell the model what is a column in the table by using the `Column` attribute:

```php
#[Column()]
private ?string $title = null;
```

The ID column is required:

```php
#[Column()]
public int $id;
```

### Querying Data

You can then get data from the database in the controller like so:

```php
$this->model->findAll();
// or
$this->model->findBy(['id' => '123']);
```

### Inserting Data

This is an example of how to insert new data into the database:

```php
$db = \Swidly\Core\DB::create();
$db->table('post')->insert([
    'post_title' => 'post title',
    'poster_id' => 123,
    'post_body' => 'this is an example post'
]);
```

Insert data using an entity, for example `PostModel`:

```php
#[Route(methods: ['POST'], path: ['/posts/add'], name: ['addPost'])]
function AddPost($req, $res) {
    $post = new PostModel();
    $post->setTitle($req->get('title'));
    $post->setBody($req->get('content'));
    $post->setCreatedAt('2022-11-11 02:46:00');
    $post->save();
}
```

### Updating Data

```php
#[Route(methods: ['POST'], path: '/posts/update', name: 'updatePost')]
function UpdatePost($req, $res) {
    $postModel = $this->model->find(['id' => 2]);
    $postModel->setTitle($req->get('title'));
    $postModel->save();
}
```

## Store Class

To use the `Store` class in your PHP project, you will need to include the `Store.php` file in your PHP script using the `require_once` statement. Once you have included the file, you can call the static methods of the `Store` class to start a session, save values to the session, retrieve a CSRF token, and check if a key exists in the session.

### Example Usage

```php
<?php
require_once 'path/to/Store.php';

// Start a session
Store::start();

// Save a value to the session
Store::save('username', 'JohnDoe');

// Retrieve the value from the session
$username = Store::get('username');

echo $username; // Output: JohnDoe
```

You can use the other static methods of the `Store` class in a similar way to handle sessions in your PHP project.

## File Class

The `File` class is a PHP class that provides methods for handling files. It has static methods for reading files, reading JSON files, reading PHP files as arrays or objects, copying files, and converting objects to JSON. The class is part of the `Swidly\Core` namespace.

### Methods

- `readFile($path)`: Reads the contents of a file and returns them as a string.
- `readJson($path)`: Reads the contents of a JSON file and returns them as a JSON-encoded string.
- `readArray($path)`: Reads the contents of a PHP file and returns them as an array or an object.
- `copyFile($source, $destination, $keepOriginal)`: Copies a file from the source path to the destination path. If `$keepOriginal` is set to `true`, the original file will be kept; otherwise, it will be deleted.
- `toJSON()`: Converts an object to a JSON-encoded string.

All of the methods in the `File` class throw a `SwidlyException` if there is an error reading or copying the file.

### Example Usage

```php
<?php
require_once 'path/to/File.php';

// Read the contents of a file
$content = File::readFile('path/to/file.txt');

echo $content; // Output: The contents of the file
```

You can use the other static methods of the `File` class in a similar way to handle files in your PHP project.

## Hooks

The `Hook` class is a PHP class that provides a simple way to implement hooks in your PHP project. It allows you to register callbacks that can be executed at specific points in your code. The class is part of the `Swidly\Core` namespace.

### Methods

- `__construct($name, $allowMultipleCalls)`: Initializes a new instance of the `Hook` class with the specified name and whether multiple calls are allowed.
- `addAction($callback, $priority, $runOnce)`: Adds a new action to the hook with the specified callback, priority, and whether the action should only be run once.
- `doCallback()`: Executes all the actions registered to the hook.
- `getHook($name)`: Returns the hook with the specified name.
- `getActions()`: Returns all the actions registered to the hook.
- `setDone()`: Marks the hook as done.

The `Hook` class uses the `Action` class to represent each action registered to the hook. The `Action` class contains a callback, priority, and whether the action should only be run once.

### Example Usage

```php
<?php
require_once 'path/to/Hook.php';

// Create a new hook
$hook = new Hook('my_hook', true);

// Add a new action to the hook
$hook->addAction(function($arg1, $arg2) {
    echo "Action 1: $arg1, $arg2\n";
}, Priority::NORMAL, false);

// Add another action to the hook
$hook->addAction(function($arg1, $arg2) {
    echo "Action 2: $arg1, $arg2\n";
}, Priority::HIGH, false);

// Execute the hook
$hook->doCallback('Hello', 'World');
```

**Output:**

```
Action 2: Hello, World
Action 1: Hello, World
```

You can use the `Hook` class to implement hooks in your PHP project to execute callbacks at specific points in your code.

---

**That's it. No download or installation required.**