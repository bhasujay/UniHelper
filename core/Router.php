<?php

namespace app\core;

class Router
{
    protected array $routes = [];
    public Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    // Adds a route for GET requests
    public function get($path, $callback)
    {
        $this->routes['GET'][$path] = $callback;
    }

    // Adds a route for POST requests
    public function post($path, $callback)
    {
        $this->routes['POST'][$path] = $callback;
    }

    // Resolves the route based on the request method and path
    public function resolve()
    {
        $path = $this->request->getPath();
        $method = $this->request->getMethod();
        
        // First check for exact match
        if (isset($this->routes[$method][$path])) {
            $callback = $this->routes[$method][$path];
            
            if (is_string($callback))
                return $this->render_view($callback);
                
            if (is_array($callback)) {
                $controller = new $callback[0]();
                $method = $callback[1];
                return $controller->$method();
            }
            
            return call_user_func($callback);
        }
        
        // Check for pattern match with parameters
        foreach ($this->routes[$method] as $route => $callback) {
            if (strpos($route, ':') !== false) {
                $routePattern = preg_replace('/:(\w+)/', '(?P<$1>[^/]+)', $route);
                $routePattern = "#^$routePattern$#";
                
                if (preg_match($routePattern, $path, $matches)) {
                    // Extract parameters
                    $params = array_filter($matches, function($key) {
                        return !is_numeric($key);
                    }, ARRAY_FILTER_USE_KEY);
                    
                    if (is_string($callback))
                        return $this->render_view($callback);
                        
                    if (is_array($callback)) {
                        $controller = new $callback[0]();
                        $method = $callback[1];
                        return $controller->$method($params);
                    }
                    
                    return call_user_func_array($callback, [$params]);
                }
            }
        }
        
        // Handle 404
        http_response_code(404);
        return "404 Not Found";
    }

    public function render_view($view)
    {
        require_once Application::$ROOT_DIR . "/views/$view";
    }
}