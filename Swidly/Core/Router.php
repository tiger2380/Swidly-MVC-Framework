<?php

namespace Swidly\Core;

class Router
{
    private const ALLOWED_METHODS = ['get', 'post', 'delete', 'put', 'update'];
    private const DEFAULT_ERROR_CODE = 500;

    protected array $GET = [];
    protected array $POST = [];
    protected array $DELETE = [];
    protected array $PULL = [];
    protected array $UPDATE = [];
    public static array $routes = [];
    protected string $groupPrefix = '';
    protected array $options = [];

    public function __construct(
        private Request $request = new Request, 
        private Response $response = new Response,
        private View $view = new View()
    ) {
    }

    static function getRoutes(): array
    {
        (new Swidly())->run(false);
        return self::$routes;
    }

    /**
     * Validates the pattern and callback before adding a route
     * @throws SwidlyException
     */
    private function validateRoute(string $pattern, mixed $callback): void 
    {
        if (empty($pattern)) {
            throw new SwidlyException('Route pattern cannot be empty', 400);
        }

        if (!is_callable($callback) && !is_string($callback)) {
            throw new SwidlyException('Invalid route callback', 400);
        }
    }

    /**
     * Adds middleware and filters to a route
     */
    private function addRouteFilters(string $pattern): void
    {
        if (isset($this->options['before'])) {
            Swidly::addBefore($this->groupPrefix.$pattern, $this->options['before']);
        }
        if (isset($this->options['after'])) {
            Swidly::addAfter($this->groupPrefix.$pattern, $this->options['after']);
        }
    }

    public function get(string $pattern, mixed $callback): self 
    {
        $this->validateRoute($pattern, $callback);
        $this->addRouteFilters($pattern);
        self::$routes['get'][$this->groupPrefix.$pattern] = $callback;
        return $this;
    }

    public function post(string $pattern, mixed $callback): self 
    {
        $this->validateRoute($pattern, $callback);
        $this->addRouteFilters($pattern);
        self::$routes['post'][$this->groupPrefix.$pattern] = $callback;
        return $this;
    }

    public function delete(string $pattern, mixed $callback): self 
    {
        $this->validateRoute($pattern, $callback);
        $this->addRouteFilters($pattern);
        self::$routes['delete'][$this->groupPrefix.$pattern] = $callback;
        return $this;
    }

    public function put(string $pattern, mixed $callback): self 
    {
        $this->validateRoute($pattern, $callback);
        $this->addRouteFilters($pattern);
        self::$routes['put'][$this->groupPrefix.$pattern] = $callback;
        return $this;
    }

    // Similar changes for post(), delete(), put(), update() methods...

    public function any(string $pattern, mixed $callback): self 
    {
        $this->validateRoute($pattern, $callback);
        foreach (self::ALLOWED_METHODS as $method) {
            $this->$method($pattern, $callback);
        }
        return $this;
    }

    public function group(array $options, callable $callback): self 
    {
        $previousPrefix = $this->groupPrefix;
        $this->groupPrefix .= $options['prefix'] ?? '';
        $this->options = $options;

        try {
            call_user_func($callback, $this);
        } finally {
            $this->groupPrefix = $previousPrefix;
        }
        return $this;
    }

    /**
     * Executes route middleware and filters
     * @throws SwidlyException
     */
    private function executeRouteMiddleware(string $path): void
    {
        if (array_key_exists($path, Swidly::$middlewares)) {
            foreach (Swidly::$middlewares[$path] as $middleware) {
                if (is_callable($middleware)) {
                    call_user_func_array($middleware, array(&$this->request, &$this->response));
                } elseif (is_string($middleware) && class_exists($middleware)) {
                    (new $middleware)->execute($this->request, $this->response);
                } else {
                    throw new SwidlyException('Invalid middleware', 500);
                }
            }
        }
    }

    /**
     * Executes the route callback
     * @throws SwidlyException
     */
    private function executeRouteCallback(mixed $callback): void
    {
        if (is_callable($callback)) {
            echo call_user_func_array($callback, array(&$this->request, &$this->response));
        } elseif (is_string($callback) && str_contains($callback, '::')) {
            [$controller, $method] = explode('::', $callback);
            if (class_exists($controller)) {
                $class = new $controller();
                if (method_exists($class, $method)) {
                    echo call_user_func_array(array($class, $method), array(&$this->request, &$this->response));
                } else {
                    throw new SwidlyException("Method $method not found in controller", 500);
                }
            } else {
                throw new SwidlyException("Controller $controller not found", 500);
            }
        } else {
            throw new SwidlyException('Invalid route callback', 400);
        }
    }

    /**
     * Builds route paths handling optional parameters and path variants
     * 
     * @param array<string,mixed> $routes Array of route patterns and their callbacks
     * @return array<string,mixed> Processed routes with optional parameters handled
     * @throws SwidlyException If route pattern is invalid
     */
    private function buildRoutePaths(array $routes): array 
    {
        if (empty($routes)) {
            return [];
        }

        // Process optional parameters first
        $processedRoutes = [];
        foreach ($routes as $path => $callback) {
            if (!is_string($path)) {
                throw new SwidlyException('Route path must be a string', 400);
            }

            // Handle optional parameters (e.g., /users/?:id/)
            if (str_contains($path, '?:')) {
                // Create route without optional parameter
                $baseRoute = preg_replace("#/\?\:\w+/?#", "", $path);
                if ($baseRoute !== false) {
                    $processedRoutes[$baseRoute] = $callback;
                }
                
                // Create route with optional parameter
                $fullRoute = str_replace('?:', ':', $path);
                $processedRoutes[$fullRoute] = $callback;
            } else {
                $processedRoutes[$path] = $callback;
            }
        }

        // Handle parameter variations
        $parameterVariations = [];
        foreach ($processedRoutes as $path => $callback) {
            // Extract parameter names
            if (preg_match_all("#\:(\w+)#", $path, $matches)) {
                foreach ($matches[1] as $param) {
                    // Create variations with and without trailing slashes
                    $variations = [
                        rtrim($path, '/'),
                        rtrim($path, '/') . '/',
                    ];
                    
                    foreach ($variations as $variant) {
                        $parameterVariations[$variant] = $callback;
                    }
                }
            }
        }

        // Merge and return unique routes
        return array_merge($processedRoutes, $parameterVariations);
    }

    private function matchRoute($route, $path, $callback): void
    {
        $pattern = preg_replace("#\:(\w+)#", '(?<$1>[^/]+)', $path);
        $pattern_regex = "#^". trim($pattern, "/") . "$#";
        preg_match($pattern_regex, trim($route, '/'), $matches);

        if($matches) {            
            foreach($matches as $id => $match) {
                $this->request->set($id, $match);
            }

            $this->executeRouteMiddleware($path);
    
            if (array_key_exists($path, Swidly::$befores)) {
                foreach(Swidly::$befores[$path] as $before) {
                    if(array_key_exists($before, Swidly::$filters)) {
                        Swidly::$filters[$before][0]($this->request, $this->response);
                    }
                }
            }

            $this->executeRouteCallback($callback);

            if (array_key_exists($path, Swidly::$afters)) {
                foreach(Swidly::$afters[$path] as $after) {
                    if(array_key_exists($after, Swidly::$filters)) {
                        Swidly::$filters[$after][0]($this->request, $this->response);
                    }
                }
            }
            
            exit();
        }
    }

    protected function setHeaders(): void 
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        if ($origin === 'http://localhost:8800') {
            header('Access-Control-Allow-Origin: http://localhost:8800');
            header('Access-Control-Allow-Credentials: true');
        }
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS, COOKIE');
        header('Access-Control-Allow-Headers: Content-Type');
        header('Access-Control-Max-Age: 3600');
        header('Access-Control-Allow-Credentials', true);
        header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
        header("X-Content-Type-Options: nosniff");
        header("X-Frame-Options: SAMEORIGIN");
        header("X-XSS-Protection: 1; mode=block");
        
        // Handle preflight OPTIONS request
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            header('HTTP/1.1 200 OK');
            exit();
        }
    }


    protected function map(): void
    {
        $this->setHeaders();

        $requestType = strtolower($this->request->getType());
        $route = $this->request->getUri();
        $filePath = $_SERVER['DOCUMENT_ROOT'] . '/' . $route;

        if (file_exists($filePath) && is_file($filePath)) {
            // Serve the file directly
            $this->serveFile($filePath);
            return;
        }
        $route = $this->parseRequestUri();

        if (!in_array($requestType, self::ALLOWED_METHODS)) {
            throw new SwidlyException('Method not allowed', 405);
        }

        $routes = self::$routes[$requestType] ?? [];
        if (empty($routes)) {
            throw new SwidlyException('No routes defined for ' . $requestType, 404);
        }

        $paths = $this->buildRoutePaths($routes);
        $matched = false;

        foreach ($paths as $path => $callback) {
            if ($this->matchRoute($route, $path, $callback)) {
                $matched = true;
                break;
            }
        }

        if (!$matched) {
            throw new SwidlyException('Unknown page', 404);
        }
    }

    protected function serveFile(string $filePath): void
    {
        // Define proper MIME types for different file extensions
        $mimeTypes = [
            'js' => 'application/javascript',
            'mjs' => 'application/javascript',
            'css' => 'text/css',
            'html' => 'text/html',
            'json' => 'application/json',
        ];

        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        $mimeType = $mimeTypes[$extension] ?? mime_content_type($filePath) ?? 'application/octet-stream';
        
        header('Content-Type: ' . $mimeType);
        readfile($filePath);
        exit;
    }

    private function parseRequestUri(): string
    {
        $replace = trim(dirname($this->request->serverName(), 2), '/');
        $uri = rtrim(str_replace($replace, '', $this->request->getUri()), '/');
        $uri = str_replace(APP_BASE, '', $uri);
        $exp = explode('?', $uri);

        if (isset($exp[1])) {
            parse_str($exp[1], $query);
            foreach ($query as $key => $value) {
                $this->request->set($key, $value);
            }
        }
        
        return trim($exp[0], '/');
    }

    public function run(): void
    {
        try {
            $this->map();
        } catch (SwidlyException $ex) {
            $this->handleError($ex->getMessage(), $ex->getCode());
        } catch (\Throwable $ex) {
            $this->handleError($ex->getMessage(), self::DEFAULT_ERROR_CODE);
        }
    }

    public function runInstall(): void
    {
        try {
            $basePath = Swidly::theme()['base'];
            require_once $basePath.'/install.php';
        } catch(SwidlyException $ex) {
            Response::setStatusCode($ex->getCode());
            $view = new View();
            $view->registerCommonComponents();
            $view->render('errors.' . $ex->getStatusCode(), [
                'message' => $ex->getMessage(), 
                'code' => $ex->getStatusCode(),
                'homeUrl' => Swidly::getConfig('url', '/')
            ]);
        }
    }

    private function handleError(string $message, int $code): void
    {
        Response::setStatusCode($code);
        $this->view->registerCommonComponents();

        echo $this->view->render('errors.' . $code, [
            'message' => $message,
            'code' => $code,
            'homeUrl' => Swidly::getConfig('url', '/')
        ]);
    }
}