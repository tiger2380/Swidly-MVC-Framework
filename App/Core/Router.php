<?php

namespace App\Core;

class Router
{
    protected $GET = [];
    protected $POST = [];
    protected $DELETE = [];
    protected $PULL = [];
    protected $UPDATE = [];
    private $routes = [];

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

    protected function map() {
        $replace = trim(dirname(dirname($this->request->serverName())), '/');
        $uri = rtrim(str_replace($replace, '', $this->request->getUri()), '/');
        $exp = explode('?', $uri);
        $route = array_shift($exp);
        $query_string = array();
        isset($exp[0]) ? parse_str($exp[0], $query_string) : null;
        $requestType = strtolower($this->request->getType());
        $routes = $this->routes[$requestType];

        $newRoute = [];
        foreach($routes as $path => $callback) {
            //optinal parameter
            if(stripos($path, '?:') > -1) {
                $stripped = preg_replace("/\/\?\:\w+\/?/", "", $path);
                $newRoute[$stripped] = $callback;
            }
        }

        $tempPaths = [];
        $callback = function($key, $value) use (&$tempPaths) {
            $tempPaths[str_replace('?', '', $value)] = $key;
        };
        array_walk($routes, $callback);

        $paths = array_merge($newRoute, $tempPaths);
        $pathsOnly = array_keys($paths);
        $pattern_regex = preg_replace("#\:(\w+)#", '(?<$1>[\w._-]+)', $pathsOnly);

        foreach($pattern_regex as $key => $pattern) {
            $pattern_regex = "#^". trim($pattern, "/") . "$#";
            preg_match($pattern_regex, trim($route, '/'), $matches);

            if($matches) {
                $results = array_merge_recursive($matches, $query_string);
                
                foreach($results as $id => $match) {
                    $this->request->set($id, $match);
                }

                if(array_key_exists($pathsOnly[$key], App::$middlewares)) {
                    foreach(App::$middlewares[$pathsOnly[$key]] as $middleware) {
                        if (is_callable($middleware)) {
                            call_user_func_array($middleware, array(&$this->request, &$this->response));
                        } else {
                            $middleware->execute($this->request, $this->response);
                        }
                    }
                }

                $callback = $paths[$pathsOnly[$key]];
                if(is_callable($callback)) {
                    call_user_func_array($callback, array(&$this->request, &$this->response));
                } else if (is_string($callback)) {
                    if(strstr($callback, '::')) {
                        list($controller, $method) = explode('::', $callback);
                        $className = '\App\Controllers\\'.$controller;
                        $class = new $className();
                        if(class_exists($className)) {
                            call_user_func_array(array($class, $method), array(&$this->request, &$this->response));
                            exit;
                        }
                    }
                } else {
                    throw new \App\Core\AppException(404, 'Unknown callable.');
                }
                exit();
            }
        }

        throw new \App\Core\AppException(404, 'Unknown page.');
    }

    public function run() {
        try {
            $this->map();
        } catch (\App\Core\AppException $ex) {
            Response::setStatusCode($ex->getCode());
            print($ex->getMessage());
            exit();
        }
    }
}