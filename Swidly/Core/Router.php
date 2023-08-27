<?php

namespace Swidly\Core;

class Router
{
    protected $GET = [];
    protected $POST = [];
    protected $DELETE = [];
    protected $PULL = [];
    protected $UPDATE = [];
    public array $routes = [];

    public function __construct(
        private Request $request = new Request, 
        private Response $response = new Response)
    {
    }

    public function get($pattern, $callback): self {
        $this->routes['get'][$pattern] = $callback;
        return $this;
    }

    public function post($pattern, $callback): self {
        $this->routes['post'][$pattern] = $callback;
        return $this;
    }

    public function delete($pattern, $callback): self {
        $this->routes['delete'][$pattern] = $callback;
        return $this;
    }

    public function put($pattern, $callback): self {
        $this->routes['put'][$pattern] = $callback;
        return $this;
    }

    public function update($pattern, $callback) {
        $this->routes['update'][$pattern] = $callback;
        return $this;
    }

    public function getRoutes(): void
    {
        dump($this->routes);
    }

    /**
     * @throws SwidlyException
     */
    protected function map(): void
    {
        $replace = trim(dirname($this->request->serverName(), 2), '/');
        $uri = rtrim(str_replace($replace, '', $this->request->getUri()), '/');
        $exp = explode('?', $uri);

        $route = array_shift($exp);
        $query_string = array();
        isset($exp[1]) ? parse_str($exp[1], $query_string) : null;
        $requestType = strtolower($this->request->getType());
        $routes = $this->routes[$requestType];

        $newRoute = [];
        foreach($routes as $path => $callback) {
            //optional parameter
            if(stripos($path, '?:') > -1) {
                $stripped = preg_replace("/\/\?\:\w+\/?/", "", $path);
                $newRoute[$stripped] = $callback;
                continue;
            }
            $newRoute[$path] = $callback;
        }

        $tempPaths = [];
        $callback = function($key, $value) use (&$tempPaths) {
            $tempPaths[str_replace('?', '', $value)] = $key;
        };
        array_walk($routes, $callback);

        $paths = array_merge($newRoute, $tempPaths);
        $pathsOnly = array_keys($paths);
        $pattern_regex = preg_replace("#\:(\w+)#", '(?<$1>[^/]+)', $pathsOnly);

        foreach($pattern_regex as $key => $pattern) {
            $pattern_regex = "#^". trim($pattern, "/") . "$#";
            preg_match($pattern_regex, trim($route, '/'), $matches);

            if($matches) {
                $results = array_merge_recursive($matches, $query_string);
                
                foreach($results as $id => $match) {
                    $this->request->set($id, $match);
                }

                if(array_key_exists($pathsOnly[$key], Swidly::$middlewares)) {
                    foreach(Swidly::$middlewares[$pathsOnly[$key]] as $middleware) {
                        if (is_callable($middleware)) {
                            call_user_func_array($middleware, array(&$this->request, &$this->response));
                        } else {
                            (new $middleware)->execute($this->request, $this->response);
                        }
                    }
                }

                $callback = $paths[$pathsOnly[$key]];
                if(is_callable($callback)) {
                    call_user_func_array($callback, array(&$this->request, &$this->response));
                } else if (is_string($callback)) {
                    if(str_contains($callback, '::')) {
                        list($controller, $method) = explode('::', $callback);
                        if(class_exists($controller)) {
                            $class = new $controller();
                            call_user_func_array(array($class, $method), array(&$this->request, &$this->response));
                            exit;
                        }
                    }
                } else {
                    throw new \Swidly\Core\SwidlyException('Unknown callable.', 400);
                }
                exit();
            }
        }

        throw new \Swidly\Core\SwidlyException('Unknown page.', 404);
    }

    public function run(): void
    {
        try {
            $this->map();
        } catch (SwidlyException $ex) {
            Response::setStatusCode($ex->getCode());
            (new Controller())->render('error', ['message' => $ex->getMessage()]);
        } catch (Throwable $ex) {
            Response::setStatusCode(500);
            (new Controller())->render('error', ['message' => 'Server error']);
        }
    }

    public function run_single_page(): void
    {
        try {
            $basePath = Swidly::theme()['base'];
            require_once $basePath.'/index.php';
        } catch(SwidlyException $ex) {
            Response::setStatusCode($ex->getCode());
            (new Controller())->render('404', ['message' => $ex->getMessage()]);
        }
    }
}