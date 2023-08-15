<?php
declare(strict_types=1);

namespace Swidly\Core;

use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use Swidly\Core\Attributes\Middleware;
use Swidly\Core\Attributes\Route;


class Swidly {
    public static array $middlewares = [];
    public static array $routeNames = [];
    public static ?array $config = null;
    protected ?string $next = null;
    protected bool $isSinglePage = false;

    /**
     * @param Response $response
     * @param Form $form
     * @param Request $request
     * @param Router $router
     */
    public function __construct(
        public Response            $response = new Response,
        public Form                $form = new Form(),
        protected Request          $request = new Request,
        private readonly Router    $router = new Router,
    )
    {
        $this->isSinglePage = self::getConfig('app::single_page', false);
    }

    /**
     * @param string $key
     * @param string|\Closure $value
     * @return $this
     */
    public function get(string $key, string|\Closure $value): self {
        $this->router->get($key, $value);
        $this->next = $key;
        return $this;
    }

    /**
     * @param string $key
     * @param string|\Closure $value
     * @return $this
     */
    public function post(string $key, string|\Closure $value): self {
        $this->router->post($key, $value);
        $this->next = $key;
        return $this;
    }

    /**
     * @param string $key
     * @param string|\Closure $value
     * @return $this
     */
    public function delete(string $key, string|\Closure $value): self {
        $this->router->delete($key, $value);
        $this->next = $key;
        return $this;
    }

    /**
     * @param string $key
     * @param string|\Closure $value
     * @return $this
     */
    public function put(string $key, string|\Closure $value): self {
        $this->router->put($key, $value);
        $this->next = $key;
        return $this;
    }

    /**
     * @param string $routeName
     * @return $this
     */
    public function name(string $routeName): self {
        self::$routeNames[$routeName] = stripQuestionMarks($this->next);
        return $this;
    }

    /**
     * @param Callable|string $middleware
     * @return $this
     */
    public function registerMiddleware(\Callable|string $middleware): self {
        self::$middlewares[stripQuestionMarks($this->next)][] = $middleware;
        return $this;
    }

    /**
     * @param string|array $methods
     * @param string|array $paths
     * @param string|callable $callback
     * @param string|null $routeName
     * @return void
     */
    public function addRoute(string|array $methods, string|array $paths, string|callable $callback, string $routeName = null): void
    {
        if (!is_string($methods) && !is_array($methods)) {
            throw new InvalidArgumentException('The $methods parameter must be a string or an array.');
        }

        if (!is_string($paths) && !is_array($paths)) {
            throw new InvalidArgumentException('The $paths parameter must be a string or an array.');
        }

        if (!is_string($callback) && !is_callable($callback)) {
            throw new InvalidArgumentException('The $callback parameter must be a string or a callable.');
        }

        if (is_array($methods)) {
            foreach ($methods as $method) {
                if (!in_array($method, self::HTTP_METHODS)) {
                    throw new InvalidArgumentException(sprintf('Invalid HTTP method "%s".', $method));
                }

                if (is_array($paths)) {
                    foreach ($paths as $path) {
                        if (!is_string($path)) {
                            throw new InvalidArgumentException('The $paths parameter must be a string or an array of strings.');
                        }

                        $this->router->routes[strtolower($method)][$path] = $callback;
                    }
                } else {
                    if (!is_string($paths)) {
                        throw new InvalidArgumentException('The $paths parameter must be a string or an array of strings.');
                    }

                    $this->router->routes[strtolower($method)][$paths] = $callback;
                }
            }
        } else {
            if (!in_array($methods, self::HTTP_METHODS)) {
                throw new InvalidArgumentException(sprintf('Invalid HTTP method "%s".', $methods));
            }

            if (is_array($paths)) {
                foreach ($paths as $path) {
                    if (!is_string($path)) {
                        throw new InvalidArgumentException('The $paths parameter must be a string or an array of strings.');
                    }

                    $this->router->routes[strtolower($methods)][$path] = $callback;
                }
            } else {
                if (!is_string($paths)) {
                    throw new InvalidArgumentException('The $paths parameter must be a string or an array of strings.');
                }

                $this->router->routes[strtolower($methods)][$paths] = $callback;
            }
        }

        if (isset($routeName)) {
            $this->next = $paths;
            $this->name($routeName);
        }
    }

    /**
     * @param ReflectionClass $reflectionClass
     * @return void
     */
    public function registerRoutes(ReflectionClass $reflectionClass): void
    {
        if (!$reflectionClass instanceof ReflectionClass) {
            throw new InvalidArgumentException('The $reflectionClass parameter must be a ReflectionClass object.');
        }

        try {
            $className = $reflectionClass->getName();
            $methods = $reflectionClass->getMethods();
        } catch (Exception $e) {
            // Handle the exception appropriately
            return;
        }

        foreach ($methods as $method) {
            try {
                $attributes = $method->getAttributes(Route::class);
            } catch (Exception $e) {
                // Handle the exception appropriately
                continue;
            }

            foreach ($attributes as $attribute) {
                try {
                    $instance = $attribute->newInstance();
                } catch (Exception $e) {
                    // Handle the exception appropriately
                    continue;
                }

                $methods = $instance->methods;
                $path = $instance->path;
                $name = $instance->name ?? null;

                try {
                    $this->addRoute($methods, $path, $className.'::'.$method->getName(), $name);
                    $this->registerMiddlewares($method, $path);
                } catch (Exception $e) {
                    // Handle the exception appropriately
                    continue;
                }
            }
        }
    }

    /**
     * @param ReflectionMethod $method
     * @param array|string $paths
     * @return void
     */
    public function registerMiddlewares(ReflectionMethod $method, array|string $paths): void
    {
        if (!$method instanceof ReflectionMethod) {
            throw new InvalidArgumentException('The $method parameter must be a ReflectionMethod object.');
        }

        if (!is_array($paths) && !is_string($paths)) {
            throw new InvalidArgumentException('The $paths parameter must be an array or a string.');
        }

        try {
            $attributes = $method->getAttributes(Middleware::class);
        } catch (Exception $e) {
            // Handle the exception appropriately
            return;
        }

        if (\count($attributes) > 0) {
            foreach ($attributes as $attribute) {
                try {
                    $instance = $attribute->newInstance();
                } catch (Exception $e) {
                    // Handle the exception appropriately
                    continue;
                }

                $callback = $instance->callback;

                try {
                    if (is_array($paths)) {
                        foreach ($paths as $path) {
                            $this->next = $path;
                            $this->registerMiddleware($callback);
                        }
                    } else {
                        $this->next = $paths;
                        $this->registerMiddleware($callback);
                    }
                } catch (Exception $e) {
                    // Handle the exception appropriately
                    continue;
                }
            }
        }
    }

    /**
     * @return bool
     */
    static public function isSinglePage(): bool
    {
        $info = self::getThemeInfo();
        return $info['single_page'] === true;
    }

    /**
     * @return bool
     */
    static public function isRequestJson(): bool
    {
        return (new Request)->get('HTTP_CONTENT_TYPE') === 'application/json';
    }

    /**
     * @return bool
     */
    static public function isFormRequest(): bool
    {
        $contentType = (new Request())->get('HTTP_CONTENT_TYPE');
        return false !== strpos($contentType, 'multipart/form-data;');
    }

    /**
     * @throws ReflectionException
     */
    public function run(): void {
        if(file_exists(APP_PATH.'/routes.php')) {
            require_once APP_PATH . '/routes.php';
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

    /**
     * @throws ReflectionException
     */
    public function loadControllerRoutes(): void
    {
        $theme = self::getConfig('theme', 'default');
        $controllerRootPath = 'Swidly/themes/'. $theme;
        $controllerPath = $controllerRootPath .'//controllers/';
        $controllers = array_diff(scandir($controllerPath), array('.', '..'));
        $controllerFilenames = array_map(fn($controller) => pathinfo($controller)['filename'], $controllers);

        foreach ($controllerFilenames as $controller) {
            $class = str_replace('/', '\\', $controllerRootPath).'\\controllers\\'.$controller;
            $this->registerRoutes(new \ReflectionClass($class));
        }
    }

    /**
     * @param string|null $page
     * @return void
     */
    static function activeLink(?string $page = null): void {
        $result = '';
        $self = new self;
        if(isset($page)) {
            if($page === $self->request->get('path', '/')) {
                $result = ' active';
            } 
        }
        
        echo $result;
    }

    /**
     * @param string $url
     * @return array
     */
    public function getNameByURL(string $url): array {
        $values = array_reverse(Swidly::$routeNames);
        global $base_url;
        
        $newArray = array_filter($values, function($var) use ($base_url, $url) {
            return $base_url.$var === $url;
        });
        
        return reset($newArray);
    }

    /**
     * @param string $name
     * @param array $params
     * @return string
     */
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

    /**
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
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

    /**
     * @param string $path
     * @return string
     */
    static function cleanPath(string $path): string {

        $path = str_replace('\\', '/', $path);

        if(strpos($path, '//') !== false) {
            $path = str_replace('//', '/', $path);
        }

        return file_exists($path) ? $path : '';
    }

    /**
     * @return array
     * @throws SwidlyException
     */
    static function theme(): array
    {
        $themeName = self::getConfig('theme', 'default');
        $themePath = APP_PATH.'/themes/'.$themeName;

        if(!file_exists($themePath)) {
            throw new SwidlyException('Unknown theme: '.$themeName);
        }
        $dir = $themePath;
        $url = self::getConfig('url').'/Swidly/themes/'.$themeName;

        return [
            'name' => $themeName,
            'base' => $dir,
            'url' => $url,
            'info' => self::cleanPath($dir.'/theme.php'),
            'screenshot' => self::cleanPath($dir.'/screenshot.png'),
        ];
    }

    /**
     * @param string $name
     * @return void
     * @throws SwidlyException
     */
    static function load_js_module(string $name): void {
        if(filter_var($name, FILTER_VALIDATE_URL)) {
            echo '<script type="text/javascript" src="'. $name .'"></script>';
        } else {
            $themePath = self::theme();
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
        
        throw new SwidlyException('Unable to load module');
    }

    /**
     * @return void
     * @throws SwidlyException
     */
    static function load_single_page() {
        $core_js = APP_CORE.'/scripts/app.js';
        if(file_exists($core_js)) {
            $core_js_path = '/Swidly/Core/scripts/app.js';
            echo '<script type="module" defer src="'.$core_js_path.'"></script>';
            return;
        }

        throw new SwidlyException('Unable to load core js', 400);
    }

    /**
     * @param string $name
     * @return void
     * @throws SwidlyException
     */
    static function load_stylesheet_module(string $name):void {
        if(filter_var($name, FILTER_VALIDATE_URL)) {
            echo '<link rel="stylesheet" href="'. $name .'">';
            return;
        } else {
            $themePath = self::theme();
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
        
        throw new SwidlyException('Unable to load module');
    }

    /**
     * @param array $pages
     * @return bool
     */
    static function hideOnPage(array $pages = []) {
        $self = new self();
        $path = $self->request->get('path', '/');
        if(count($pages) > 0 && in_array($path, $pages)) {
            return false;
        }
        return true;
    }

    static function getThemeInfo(): array
    {
        return File::readArray(self::theme()['info']);
    }

    static function getTitle(): string
    {
        $info = self::getThemeInfo();
        return $info['title'] ?? '';
    }

    static function getThemeName(): string
    {
        $info = self::getThemeInfo();
        return $info['name'] ?? '';
    }
}