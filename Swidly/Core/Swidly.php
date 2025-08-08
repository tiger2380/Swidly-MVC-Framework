<?php
declare(strict_types=1);

namespace Swidly\Core;

use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use Swidly\Core\Attributes\Middleware;
use Swidly\Core\Attributes\Route;
use Swidly\Core\Attributes\RouteGroup;


class Swidly {
    public static array $middlewares = [];
    public static array $routeNames = [];
    public static array $befores = [];
    public static array $afters = [];
    public static ?array $config = null;
    protected ?string $next = null;
    protected bool $isSinglePage = false;
    public static array $filters = [];
    /**
     * The singleton instance of the Swidly class
     *
     * @var Swidly|null
     */
    private static ?self $instance = null;


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
        private Controller         $controller = new Controller,
    )
    {
        $this->isSinglePage = self::getConfig('app::single_page', false);
    }

    /**
     * Get the singleton instance of the Swidly application
     *
     * This method implements the singleton pattern to ensure
     * there's only one instance of the Swidly application available
     * throughout the application lifecycle.
     *
     * @return self The singleton instance of Swidly
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
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
     * @param string $key
     * @param string|\Closure $value
     * @return $this
     */
    public function group(array $options, string|\Closure $value): self {
        $this->router->group($options, $value);
        $this->next = $options['prefix'] ?? '';
        return $this;
    }

    public function filter(string $name, string|\Closure $callback): self {
        if (!is_callable($callback)) {
            throw new \InvalidArgumentException('The $callback parameter must be a callable.');
        }

        self::$filters[stripQuestionMarks($name)][] = $callback;
        return $this;
    }

    public static function addBefore(string $pattern, string $filterName) {
        if (!is_string($filterName)) {
            throw new \InvalidArgumentException('The $filterName parameter must be a string.');
        }

        self::$befores[$pattern][] = $filterName;
    }
    
    public static function addAfter(string $pattern, string $filterName) {
        if (!is_string($filterName)) {
            throw new \InvalidArgumentException('The $filterName parameter must be a string.');
        }

        self::$afters[$pattern][] = $filterName;
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
     * @param Closure|string $middleware
     * @return $this
     */
    public function registerMiddleware(\Closure|string $middleware): self {
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
    public function addRoute(string|array $methods, string|array $paths, string|callable $callback, $routeName = null, string $groupPrefix = ''): void
    {
        if (!is_string($methods) && !is_array($methods)) {
            throw new \InvalidArgumentException('The $methods parameter must be a string or an array.');
        }

        if (!is_string($paths) && !is_array($paths)) {
            throw new \InvalidArgumentException('The $paths parameter must be a string or an array.');
        }

        if (!is_string($callback) && !is_callable($callback)) {
            throw new \InvalidArgumentException('The $callback parameter must be a string or a callable.');
        }

        if (!empty($groupPrefix) && !is_string($groupPrefix)) {
            throw new \InvalidArgumentException('The $groupPrefix parameter must be a string.');
        }

        if (is_array($methods)) {
            foreach ($methods as $method) {
                if (is_array($paths)) {
                    foreach ($paths as $path) {
                        if (!is_string($path)) {
                            throw new \InvalidArgumentException('The $paths parameter must be a string or an array of strings.');
                        }

                        $this->router::$routes[strtolower($method)][$groupPrefix.$path] = $callback;
                    }
                } else {
                    if (!is_string($paths)) {
                        throw new \InvalidArgumentException('The $paths parameter must be a string or an array of strings.');
                    }

                    $this->router::$routes[strtolower($method)][$groupPrefix.$paths] = $callback;
                }
            }
        } else {
            if (is_array($paths)) {
                foreach ($paths as $path) {
                    if (!is_string($path)) {
                        throw new \InvalidArgumentException('The $paths parameter must be a string or an array of strings.');
                    }

                    $this->router::$routes[strtolower($methods)][$groupPrefix.$path] = $callback;
                }
            } else {
                if (!is_string($paths)) {
                    throw new \InvalidArgumentException('The $paths parameter must be a string or an array of strings.');
                }

                $this->router::$routes[strtolower($methods)][$groupPrefix.$paths] = $callback;
            }
        }

        if (isset($routeName)) {
            if (is_array($paths)) {
                foreach($paths as $path) {
                    $this->next = $path;
                    $this->name($routeName);
                }
            } else {
                $this->next = $paths;
                $this->name($routeName);
            }
        }
    }

    /**
     * @param ReflectionClass $reflectionClass
     * @return void
     */
    public function registerRoutes(ReflectionClass $reflectionClass): void
    {
        if (!$reflectionClass instanceof ReflectionClass) {
            throw new \InvalidArgumentException('The $reflectionClass parameter must be a ReflectionClass object.');
        }
        
        try {
            $className = $reflectionClass->getName();
            $methods = $reflectionClass->getMethods();
            $groupAttributes = $reflectionClass->getAttributes(RouteGroup::class);
            $groupPrefix = $groupAttributes ? $groupAttributes[0]->newInstance()->prefix : '';
        } catch (\Exception $e) {
            // Handle the exception appropriately
            return;
        }

        foreach ($methods as $method) {
            try {
                $attributes = $method->getAttributes(Route::class);
            } catch (\Exception $e) {
                // Handle the exception appropriately
                continue;
            }

            foreach ($attributes as $attribute) {
                try {
                    $instance = $attribute->newInstance();
                } catch (\Exception $e) {
                    // Handle the exception appropriately
                    continue;
                }

                $methods = $instance->methods;
                $path = $instance->path;
                $name = $instance->name ?? null;

                try {
                    $this->addRoute($methods, $path, $className.'::'.$method->getName(), $name, $groupPrefix);
                    $this->registerMiddlewares($method, $path);
                } catch (\Exception $e) {
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
            throw new \InvalidArgumentException('The $method parameter must be a ReflectionMethod object.');
        }

        if (!is_array($paths) && !is_string($paths)) {
            throw new \InvalidArgumentException('The $paths parameter must be an array or a string.');
        }

        try {
            $attributes = $method->getAttributes(Middleware::class);
        } catch (\Exception $e) {
            // Handle the exception appropriately
            return;
        }

        if (\count($attributes) > 0) {
            foreach ($attributes as $attribute) {
                try {
                    $instance = $attribute->newInstance();
                } catch (\Exception $e) {
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
                } catch (\Exception $e) {
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
        $contentType = (new Request())->get('HTTP_CONTENT_TYPE') ?? (new Request())->get('CONTENT_TYPE');
        return $contentType == 'application/json';
    }

    static public function isAuthenticated(): bool
    {
        return isset($_SESSION[Swidly::getConfig('session_name')]);
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
    public function run($doRunCommand = true): void {
        if (self::getConfig('app::debug', false)) {
            $this->enableErrorHandling();
        }

        /*if (!File::isFile(APP_ROOT . '/installed.php')) {
            $this->router->runInstall();
            return;
        }*/

        if(file_exists(SWIDLY_ROOT.'/routes.php')) {
            require_once SWIDLY_ROOT . '/routes.php';
        } else {
            echo 'routes file doesn\'t exists';
            exit();
        }



        $this->loadControllerRoutes();

        if($doRunCommand) {
            $this->router->run();
        }
    }

    /**
     * @throws ReflectionException
     */
    public function loadControllerRoutes(): void
    {
        $theme = self::getConfig('theme', 'default');
        $controllerRootPath = __DIR__.'/../themes/'. $theme;
        $controllerPath = $controllerRootPath .'/controllers/';

        if (!is_dir($controllerPath)) {
            throw new \Exception('Directory does not exists');
        }

        $controllers = array_diff(scandir($controllerPath), array('.', '..'));
        $controllerFilenames = array_map(fn($controller) => pathinfo($controller)['filename'], $controllers);

        foreach ($controllerFilenames as $controller) {
            $class = '\\Swidly\\themes\\'.$theme.'\\controllers\\'.$controller;

            try {
                $this->registerRoutes(new \ReflectionClass($class));
            } catch (\ReflectionException $ex) {
                dump($ex->getMessage());
                die('Class does not exists: '.$class);
            }
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
            Swidly::$config = parseArray(require_once(\SWIDLY_CORE . '/config.php'));
        }
        
        if(array_key_exists($name, Swidly::$config) && !empty(Swidly::$config[$name])) {
            return Swidly::$config[$name];
        } else {
            return $default;
        }
    }
    
    /**
     * @param string $name
     * @param mixed $value
     * @return void
     * @throws SwidlyException
     */
    public function setConfigValue(string $name, mixed $value): void {
        if(!isset(Swidly::$config)) {
            Swidly::$config = parseArray(require_once(\SWIDLY_CORE . '/config.php'));
        }
        
        Swidly::$config[$name] = $value;
    }

    /**
     * @param array $config
     * @return void
     */
    public function setConfigValues(array $config): void {
        foreach ($config as $key => $value) {
            $this->setConfigValue($key, $value);
        }
    }

    public function enableErrorHandling(): void {
        error_reporting(E_ALL);
        set_error_handler([$this, 'errorHandler']);
        set_exception_handler([$this, 'exceptionHandler']);
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
        $themePath = SWIDLY_ROOT.'themes/'.$themeName;

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
            return;
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
                    return;
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
        $core_js = \SWIDLY_CORE.'/scripts/app.js';
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

    static function isLoggedIn(): bool
    {
        return isset($_SESSION[Swidly::getConfig('session_name')]);
    }

    static function render($path) {
        $self = new self();
        $self->controller->render($path);
    }
}