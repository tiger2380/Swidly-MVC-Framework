<?php

namespace Swidly\Core;

class Router
{
    protected $GET = [];
    protected $POST = [];
    protected $DELETE = [];
    protected $PULL = [];
    protected $UPDATE = [];
    public static array $routes = [];
    protected $groupPrefix = '';
    protected $options = [];

    public function __construct(
        private Request $request = new Request, 
        private Response $response = new Response)
    {
    }

    public function get($pattern, $callback): self {
        if (isset($this->options['before'])) {
            Swidly::addBefore($this->groupPrefix.$pattern, $this->options['before']);
        }
        if (isset($this->options['after'])) {
            Swidly::addAfter($this->groupPrefix.$pattern, $this->options['after']);
        }
        self::$routes['get'][$this->groupPrefix.$pattern] = $callback;
        return $this;
    }

    public function post($pattern, $callback): self {
        if (isset($this->options['before'])) {
            Swidly::addBefore($this->groupPrefix.$pattern, $this->options['before']);
        }
        if (isset($this->options['after'])) {
            Swidly::addAfter($this->groupPrefix.$pattern, $this->options['after']);
        }
        self::$routes['post'][$this->groupPrefix.$pattern] = $callback;
        return $this;
    }

    public function delete($pattern, $callback): self {
        if (isset($this->options['before'])) {
            Swidly::addBefore($this->groupPrefix.$pattern, $this->options['before']);
        }
        if (isset($this->options['after'])) {
            Swidly::addAfter($this->groupPrefix.$pattern, $this->options['after']);
        }
        self::$routes['delete'][$this->groupPrefix.$pattern] = $callback;
        return $this;
    }

    public function put($pattern, $callback): self {
        if (isset($this->options['before'])) {
            Swidly::addBefore($this->groupPrefix.$pattern, $this->options['before']);
        }
        if (isset($this->options['after'])) {
            Swidly::addAfter($this->groupPrefix.$pattern, $this->options['after']);
        }
        self::$routes['put'][$this->groupPrefix.$pattern] = $callback;
        return $this;
    }

    public function update($pattern, $callback) {
        if (isset($this->options['before'])) {
            Swidly::addBefore($this->groupPrefix.$pattern, $this->options['before']);
        }
        if (isset($this->options['after'])) {
            Swidly::addAfter($this->groupPrefix.$pattern, $this->options['after']);
        }
        self::$routes['update'][$this->groupPrefix.$pattern] = $callback;
        return $this;
    }

    public function any($pattern, $callback): self {
        $this->get($pattern, $callback);
        $this->post($pattern, $callback);
        $this->put($pattern, $callback);
        $this->delete($pattern, $callback);
        $this->update($pattern, $callback);
        return $this;
    }

    public function group(array $options, $callback): self {
        $previousPrefix = $this->groupPrefix;
        $this->groupPrefix .= $options['prefix'] ?? '';
        $this->options = $options;

        call_user_func($callback, $this);

        $this->groupPrefix = $previousPrefix;
        return $this;
    }

    public static function getRoutes(): array
    {
        (new Swidly())->run(false);
        return self::$routes;
    }

    /**
     * @throws SwidlyException
     */
    protected function map(): void
    {
        $replace = trim(dirname($this->request->serverName(), 2), '/');
        $uri = rtrim(str_replace($replace, '', $this->request->getUri()), '/');
        $uri = str_replace(APP_BASE, '', $uri);
        $exp = explode('?', $uri);

        $route = array_shift($exp);
        $query_string = array();
        isset($exp[1]) ? parse_str($exp[1], $query_string) : null;
        $requestType = strtolower($this->request->getType());
        $routes = self::$routes[$requestType];

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
        
                if (array_key_exists($pathsOnly[$key], Swidly::$befores)) {
                    foreach(Swidly::$befores[$pathsOnly[$key]] as $before) {
                        if(array_key_exists($before, Swidly::$filters)) {
                            Swidly::$filters[$before][0]($this->request, $this->response);
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
                        }
                    }
                } else {
                    throw new \Swidly\Core\SwidlyException('Unknown callable.', 400);
                }

                if (array_key_exists($pathsOnly[$key], Swidly::$afters)) {
                    foreach(Swidly::$afters[$pathsOnly[$key]] as $after) {
                        if(array_key_exists($after, Swidly::$filters)) {
                            Swidly::$filters[$after][0]($this->request, $this->response);
                        }
                    }
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
            (new Controller())->render('404', ['message' => $ex->getMessage(), 'code' => $ex->getStatusCode()]);
        } catch (\Throwable $ex) {
            Response::setStatusCode(500);
            (new Controller())->render('404', ['message' => $ex->getMessage(), 'code' => 500]);
        }
    }

    public function run_single_page(): void
    {
        try {
            $basePath = Swidly::theme()['base'];
            require_once $basePath.'/index.php';
        } catch(SwidlyException $ex) {
            Response::setStatusCode($ex->getCode());
            (new Controller())->render('404', ['message' => $ex->getMessage(), 'code' => $ex->getStatusCode()]);
        }
    }
}