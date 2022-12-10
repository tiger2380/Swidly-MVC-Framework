<?php
declare(strict_types=1);

namespace Swidly\Core;

use Swidly\Core\Attributes\Middleware;
use Swidly\Core\Attributes\Route;

define('BASENAME', dirname(__FILE__));

class Swidly {
    public static array $middlewares = [];
    public static array $routeNames = [];
    public static ?array $config = null;
    protected ?string $next = null;
    protected bool $isSinglePage = false;

    public function __construct(
        private Container $container = new Container,
        public Response $response = new Response,
        public Form $form = new Form(),
        protected Request $request = new Request,
        private Router $router = new Router,
    )
    {
        $this->isSinglePage = self::getConfig('app::single_page', false);
    }

    public function get($key, $value): self {
        $this->router->get($key, $value);
        $this->next = $key;
        return $this;
    }

    public function post($key, $value): self {
        $this->router->post($key, $value);
        $this->next = $key;
        return $this;
    }

    public function delete($key, $value): self {
        $this->router->delete($key, $value);
        $this->next = $key;
        return $this;
    }

    public function put($key, $value): self {
        $this->router->put($key, $value);
        $this->next = $key;
        return $this;
    }

    public function container($key, $value = null) {
        if(isset($value)) {
            $this->container->set($key, $value);
        } else {
            return $this->container->get($key);
        }
    }

    public function name($routeName): self {
        self::$routeNames[$routeName] = stripQuestionMarks($this->next);
        return $this;
    }

    public function registerMiddleware($middleware): self {
        self::$middlewares[stripQuestionMarks($this->next)][] = $middleware;
        return $this;
    }

    public function addRoute(string|array $methods, string|array $paths, string|callable $callback, string $routeName = null): void {
        if (is_array($methods)) {
            foreach ($methods as $method) {
                if (is_array($paths)) {
                    foreach ($paths as $path) {
                        $this->router->routes[strtolower($method)][$path] = $callback;
                    }
                } else {
                    $this->router->routes[strtolower($method)][$paths] = $callback;
                }
            }
        } else {
            if (is_array($paths)) {
                foreach ($paths as $path) {
                    $this->router->routes[strtolower($methods)][$path] = $callback;
                }
            } else {
                $this->router->routes[strtolower($methods)][$paths] = $callback;
            }
        }

        if (isset($routeName)) {
            $this->next = $paths;
            $this->name($routeName);
        }
    }

    public function registerRoutes(\ReflectionClass $reflectionClass)
    {
        $className = $reflectionClass->getName();
        $methods = $reflectionClass->getMethods();

        foreach ($methods as $method) {
            $attributes = $method->getAttributes(Route::class);

            foreach ($attributes as $attribute) {
                $instance = $attribute->newInstance();
                $methods = $instance->methods;
                $path = $instance->path;
                $name = $instance->name ?? null;

                $this->addRoute($methods, $path, $className.'::'.$method->getName(), $name);
                $this->registerMiddlewares($method, $path);
            }
        }
    }

    public function registerMiddlewares($method, $paths) {
        $attributes = $method->getAttributes(Middleware::class);
        
        if(\count($attributes) > 0) {
            foreach ($attributes as $attribute) {
                $instance = $attribute->newInstance();
                $callback = $instance->callback;

                if (is_array($paths)) {
                    foreach ($paths as $path) {
                        $this->next = $path;
                        $this->registerMiddleware($callback);
                    }
                } else {
                    $this->next = $paths;
                    $this->registerMiddleware($callback);
                }
            }
        }
    }

    static public function isSinglePage() {
        return Swidly::getConfig('app::single_page', false) === true;
    }

    static public function isRequestJson() {
        return (new Request)->get('HTTP_CONTENT_TYPE') === 'application/json';
    }

    public function run(): void {
        if(file_exists(BASENAME.'/../routes.php')) {
            require_once BASENAME . '/../routes.php';
        } else {
            echo 'routes file doesn\'t exists';
            exit();
        }

        $this->loadControllerRoutes();

        if(self::isSinglePage()) {
            $this->router->run_single_page();
        }

        $this->router->run();
    }

    public function loadControllerRoutes() {
        $controllers = array_diff(scandir(BASENAME.'/../Controllers/'), array('.', '..'));
        $controllerFilenames = array_map(fn($controller) => pathinfo($controller)['filename'], $controllers);

        foreach ($controllerFilenames as $controller) {
            $class = '\Swidly\Controllers\\'.$controller;
            $this->registerRoutes(new \ReflectionClass($class));
        }
    }


    static function activeLink($page = null): void {
        $result = '';
        $self = new self;
        if(isset($page)) {
            if($page === $self->request->get('path', '/')) {
                $result = ' active';
            } 
        }
        
        echo $result;
    }

    public function getNameByURL($url): array {
        $values = array_reverse(Swidly::$routeNames);
        global $base_url;
        
        $newArray = array_filter($values, function($var) use ($base_url, $url) {
            return $base_url.$var === $url;
        });
        
        return reset($newArray);
    }

    static function path(string $name, array $params = []) : string {
        global $base_url;
        $add_params = '';
        if(count($params) > 0) {
            $add_params = '/'.rtrim(join('/', $params), '/');
        }

        if(array_key_exists($name, Swidly::$routeNames)) {
            $route = preg_replace('/\??:\w+/', '', Swidly::$routeNames[$name]);

            if(strlen($route) > 1) {
                $route = rtrim($route, '/');
            }
            return Swidly::getConfig('url') . $route . $add_params;
        } else {
            return '';
        }
    }
    
    static function getConfig(string $name, mixed $default = ''): mixed {
        if(!isset(Swidly::$config)) {
            Swidly::$config = parseArray(require_once('Config.php'));
        }
        
        if(array_key_exists($name, Swidly::$config) && !empty(Swidly::$config[$name])) {
            return Swidly::$config[$name];
        } else {
            return $default;
        }
    }

    static function cleanPath($path): string {
        return file_exists($path) ? $path : '';
    }

    static function themePath() {
        $themeName = self::getConfig('theme', 'default');
        $themePath = BASENAME.'/../themes/'.$themeName;

        if(!file_exists($themePath)) {
            throw new AppException('Unknown theme: '.$themeName);
        }
        $dir = $themePath;
        $url = self::getConfig('url').'/Swidly/themes/'.$themeName;
        return [
            'name' => $themeName,
            'base' => $dir,
            'url' => $url,
            'info' => self::cleanPath($dir.'/'.$themeName.'.info'),
            'screenshot' => self::cleanPath($dir.'/screenshot.png'),
        ];
    }

    static function load_js_module($name): void {
        if(filter_var($name, FILTER_VALIDATE_URL)) {
            echo '<script type="text/javascript" src="'. $name .'"></script>';
        } else {
            $themePath = self::themePath();
            $jsDir = $themePath['base'].'/js';
            $dir = null;

            if($pos = strrpos($name, '/')) {
                $dir = substr($name, 0, $pos);
                $name = substr($name, $pos + 1, strlen($name));

                $jsDir = $jsDir.'/'.$dir;
            }

            $url = $themePath['url'];

            if(file_exists($jsDir)) {
                $files = glob($jsDir.'/*.js');

                $array = preg_grep('/' . $name . '\.js/i', $files);
                $jsFile = reset($array);
                $parsed_file = pathinfo($jsFile);
                if(!empty($jsFile)) {
                    echo '<script type="module" src="'. $url.'/js/'.( isset($dir) ? $dir.'/' : '' ).$parsed_file['basename'].'?t='. time()  .'"></script>';
                }
            }
        }
        
        throw new AppException('Unable to load module');
    }

    static function load_single_page() {
        $core_js = BASENAME.'/scripts/app.js';
        if(file_exists($core_js)) {
            $core_js_path = '/Swidly/Core/scripts/app.js';
            echo '<script type="module" defer src="'.$core_js_path.'"></script>';
            return;
        }

        throw new AppException('Unable to load core js', 400);
    }

    static function load_stylesheet_module($name) {
        if(filter_var($name, FILTER_VALIDATE_URL)) {
            echo '<link rel="stylesheet" href="'. $name .'">';
            return;
        } else {
            $themePath = self::themePath();
            $cssDir = $themePath['base'].'/css';

            if($pos = strrpos($name, '/')) {
                $dir = substr($name, 0, $pos);
                $name = substr($name, $pos + 1, strlen($name));

                $cssDir = $cssDir.'/'.$dir;
            }

            $url = $themePath['url'];

            if(file_exists($cssDir)) {
                $files = glob($cssDir.'/*.css');

                $array = preg_grep('/' . $name . '\.css/i', $files);
                $cssFile = reset($array);
                $parsed_file = pathinfo($cssFile);
                if(!empty($cssFile)) {
                    echo '<link rel="stylesheet" href="'. $url.'/css/'.( isset($dir) ? $dir.'/' : '' ).$parsed_file['basename'].'?t='. time() .'">';
                }
                return;
            }
        }
        
        throw new \Exception('Unable to load module');
    }

    static function hideOnPage(array $pages = []) {
        $self = new self();
        $path = $self->request->get('path', '/');
        if(count($pages) > 0 && in_array($path, $pages)) {
            return false;
        }
        return true;
    }
}