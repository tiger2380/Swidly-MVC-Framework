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
    )
    {
        $theme = self::theme();
        !defined('ROOT_URL') && define('ROOT_URL', self::getConfig('app::base_url') . '/Swidly/themes/' . $theme['name']);
        !defined('WEB_URL') && define('WEB_URL', self::getConfig('app::base_url'));

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
            $groupPrefix = trim($groupPrefix, '/');

            if (is_array($paths)) {
                foreach($paths as $path) {
                    $this->next = $groupPrefix.$path;
                    if(isset($groupPrefix) && !empty($groupPrefix)) {
                        $this->name($groupPrefix.'-'.$routeName);
                    } else {
                        $this->name($routeName);
                    }
                }
            } else {
                $this->next = $groupPrefix.$paths;
                if(isset($groupPrefix) && !empty($groupPrefix)) {
                    $this->name($groupPrefix.'-'.$routeName);
                } else {
                    $this->name($routeName);
                }
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
                    $this->registerMiddlewares($method, $path, $groupPrefix);
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
    public function registerMiddlewares(ReflectionMethod $method, array|string $paths, string $groupPrefix = ''): void
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
                            $this->next = $groupPrefix.$path;
                            $this->registerMiddleware($callback);
                        }
                    } else {
                        $this->next = $groupPrefix.$paths;
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
        $request = new Request();
        $contentType = $request->getServer('HTTP_CONTENT_TYPE') ?? $request->getServer('CONTENT_TYPE');
        return $contentType == 'application/json';
    }

    static public function isAuthenticated(): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $sessionName = Swidly::getConfig('session_name');
        return isset($_SESSION[$sessionName]);
    }

    /**
     * @return bool
     */
    static public function isFormRequest(): bool
    {
        $contentType = (new Request())->get('HTTP_CONTENT_TYPE');
        return false !== strpos($contentType, 'multipart/form-data;');
    }

    public function run($doRunCommand = true): void {
        try {
            if (self::isDebugMode()) {
                $this->enableErrorHandling();
            }

            $routesFile = SWIDLY_ROOT . '/routes.php';
            if (!file_exists($routesFile) || !is_readable($routesFile)) {
                throw new \RuntimeException('Routes file does not exist or is not readable.');
            }
            require_once $routesFile;

            $this->loadControllerRoutes();

            if ($doRunCommand) {
                $this->router->run();
            }
        } catch (\Swidly\Core\SwidlyException $e) {
            $this->handleException($e);
        } catch (\Throwable $e) {
            $this->handleException($e);
        }
    }

    /**
     * @throws ReflectionException
     */
    public function loadControllerRoutes(): void
    {
        $theme = self::getConfig('theme', 'default');
        $controllerRootPath = __DIR__ . '/../themes/' . basename($theme);
        $controllerPath = $controllerRootPath . '/controllers/';

        if (!is_dir($controllerPath)) {
            throw new \RuntimeException('Controller directory does not exist: ' . $controllerPath);
        }

        $controllers = array_diff(scandir($controllerPath), array('.', '..'));
        foreach ($controllers as $controllerFile) {
            $filename = pathinfo($controllerFile, PATHINFO_FILENAME);
            if (!preg_match('/^[A-Za-z0-9_]+$/', $filename)) {
                continue; // skip suspicious files
            }
            $class = '\\Swidly\\themes\\' . $theme . '\\controllers\\' . $filename;

            try {
                $this->registerRoutes(new \ReflectionClass($class));
            } catch (\ReflectionException $ex) {
                if (self::getConfig('app::debug', false)) {
                    error_log('Class does not exist: ' . $class . ' - ' . $ex->getMessage());
                }
                continue;
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
        $currentPath = $self->request->get('path', null, null, '/');
        if (isset($page) && $page === $currentPath) {
            $result = ' active';
        }
        echo htmlspecialchars($result, ENT_QUOTES, 'UTF-8');
    }

    /**
     * @param string $url
     * @return array
     */
    public function getNameByURL(string $url): array {
        $values = array_reverse(Swidly::$routeNames);
        $base_url = self::getConfig('url', '');
        $newArray = array_filter($values, function($var) use ($base_url, $url) {
            return $base_url . $var === $url;
        });
        return reset($newArray) ?: [];
    }

    /**
     * @param string $name
     * @param array $params
     * @return string
     */
    static function path(string $name, array $params = []) : string {
        if(array_key_exists($name, Swidly::$routeNames)) {
            $route = Swidly::$routeNames[$name];

            if(strlen($route) > 1) {
                $route = rtrim($route, '/');
            }
            return Swidly::getConfig('url') . $route;
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
     * Check if debug mode is enabled
     */
    public static function isDebugMode(): bool {
        return self::getConfig('app::debug', false) || self::getConfig('DEVELOPMENT_ENVIRONMENT', false);
    }

    /**
     * Custom error handler
     */
    public function errorHandler($errno, $errstr, $errfile, $errline): bool {
        if (!(error_reporting() & $errno)) {
            return false;
        }

        $this->renderErrorPage(500, [
            'errorMessage' => $errstr,
            'errorFile' => $errfile,
            'errorLine' => $errline,
            'debugMode' => self::isDebugMode()
        ]);

        return true;
    }

    /**
     * Custom exception handler
     */
    public function exceptionHandler(\Throwable $e): void {
        $this->handleException($e);
    }

    /**
     * Handle exceptions and display custom error pages
     */
    private function handleException(\Throwable $e): void {
        // Log the error
        if (self::isDebugMode()) {
            error_log('Exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
        }

        // Determine HTTP status code
        $statusCode = 500;
        if ($e instanceof SwidlyException) {
            $statusCode = $e->getStatusCode();
        } elseif (method_exists($e, 'getCode')) {
            $statusCode = $e->getCode();
        }

        // Render appropriate error page
        $this->renderErrorPage($statusCode, [
            'errorMessage' => $e->getMessage(),
            'errorFile' => $e->getFile(),
            'errorLine' => $e->getLine(),
            'errorTrace' => $e->getTraceAsString(),
            'debugMode' => self::isDebugMode()
        ]);
    }

    /**
     * Render custom error page
     */
    private function renderErrorPage(int $statusCode, array $data = []): void {
        http_response_code($statusCode);

        // Add home URL to data
        $data['homeUrl'] = self::getConfig('url', '/');

        // Try to render theme-specific error page
        try {
            $theme = self::theme();
            $errorView = $theme['base'] . '/views/errors/' . $statusCode . '.php';

            if (file_exists($errorView)) {
                extract($data);
                require $errorView;
                exit;
            }
        } catch (\Throwable $e) {
            // If theme rendering fails, continue to fallback
        }

        // Fallback to generic error page
        $this->renderFallbackError($statusCode, $data);
        exit;
    }

    /**
     * Render a simple fallback error page
     */
    private function renderFallbackError(int $statusCode, array $data): void {
        $messages = [
            403 => 'Access Denied',
            404 => 'Page Not Found',
            500 => 'Internal Server Error'
        ];

        $message = $messages[$statusCode] ?? 'An Error Occurred';
        
        echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>' . $statusCode . ' - ' . $message . '</title>';
        echo '<style>body{font-family:sans-serif;text-align:center;padding:50px;background:#f5f5f5;}';
        echo 'h1{font-size:72px;margin:0;color:#333;}p{font-size:18px;color:#666;}</style></head><body>';
        echo '<h1>' . $statusCode . '</h1><p>' . $message . '</p>';
        
        if (!empty($data['debugMode']) && !empty($data['errorMessage'])) {
            echo '<div style="background:#fff;padding:20px;margin:20px auto;max-width:800px;text-align:left;border:1px solid #ddd;">';
            echo '<strong>Error:</strong> ' . htmlspecialchars($data['errorMessage']);
            if (!empty($data['errorFile'])) {
                echo '<br><strong>File:</strong> ' . htmlspecialchars($data['errorFile']);
            }
            if (!empty($data['errorLine'])) {
                echo '<br><strong>Line:</strong> ' . htmlspecialchars($data['errorLine']);
            }
            echo '</div>';
        }
        
        echo '<p><a href="' . htmlspecialchars($data['homeUrl'] ?? '/') . '">← Back to Home</a></p>';
        echo '</body></html>';
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
        if (filter_var($name, FILTER_VALIDATE_URL)) {
            echo '<script type="text/javascript" src="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '"></script>';
            return;
        }
        $themePath = self::theme();
        $jsDir = $themePath['base'] . '/js';
        $dir = null;

        if (($pos = strrpos($name, '/')) !== false) {
            $dir = substr($name, 0, $pos);
            $name = substr($name, $pos + 1);
            $jsDir = $jsDir . '/' . $dir;
        }

        $url = $themePath['url'];

        if (file_exists($jsDir)) {
            $files = glob($jsDir . '/*.js');
            $array = preg_grep('/' . preg_quote($name, '/') . '\.js$/i', $files);
            $jsFile = reset($array);
            if (!empty($jsFile)) {
                $parsed_file = pathinfo($jsFile);
                echo '<script type="module" src="' . htmlspecialchars($url . '/js/' . ($dir ? $dir . '/' : '') . $parsed_file['basename'], ENT_QUOTES, 'UTF-8') . '?t=' . time() . '"></script>';
                return;
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
    static function getStyle(string $name): void {
        if (filter_var($name, FILTER_VALIDATE_URL)) {
            echo '<link rel="stylesheet" href="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '">';
            return;
        }
        $themePath = self::theme();
        $cssDir = $themePath['base'] . '/assets';

        if (($pos = strrpos($name, '/')) !== false) {
            $dir = substr($name, 0, $pos);
            $name = substr($name, $pos + 1);
            $cssDir = $cssDir . '/' . $dir;
        }

        $url = self::getConfig('app::base_url') . trim($themePath['url'], '/');

        if (file_exists($cssDir)) {
            $files = glob($cssDir . '/*.css');
            $array = preg_grep('/' . preg_quote($name, '/') . '\.css$/i', $files);
            $cssFile = reset($array);
            if (!empty($cssFile)) {
                $parsed_file = pathinfo($cssFile);
                echo '<link rel="stylesheet" href="' . htmlspecialchars($url . '/assets/' . ($dir ? $dir . '/' : '') . $parsed_file['basename'], ENT_QUOTES, 'UTF-8') . '?t=' . time() . '">';
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
        $path = $self->request->get('path', null, null, '/');
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

    static function render($path, $data = []): void{
        $view = new View();
        $view->registerCommonComponents();
        echo $view->render($path, $data);
    }
}