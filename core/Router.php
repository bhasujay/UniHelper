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

    // Adds a route for PUT requests
    public function put($path, $callback)
    {
        $this->routes['PUT'][$path] = $callback;
    }

    // Adds a route for DELETE requests
    public function delete($path, $callback)
    {
        $this->routes['DELETE'][$path] = $callback;
    }

    // Resolves the route based on the request method and path
    public function resolve()
    {
        $path = $this->request->getPath();
        $method = $this->request->getMethod();
        $queryParams = $this->request->getQueryParams();
        
        // First check for exact match
        if (isset($this->routes[$method][$path])) {
            $callback = $this->routes[$method][$path];
            
            // if the callback is a string, render the view
            if (is_string($callback)) {
                return $this->render_view($callback);
            }

            // if the callback is an array, instantiate the controller and call the method
            if (is_array($callback)) {
                $controller = new $callback[0]();
                $method = $callback[1];
                return $controller->$method($this->request);
            }
            
            // if the callback is a closure, call it directly
            return call_user_func($callback);
        }
        
        // Check for pattern match with parameters
        foreach ($this->routes[$method] as $route => $callback) {
            if (strpos($route, ':') !== false) {
                $routePattern = preg_replace('/:(\w+)/', '(?P<$1>[^/]+)', $route);
                $routePattern = "#^$routePattern$#";
                
                if (preg_match($routePattern, $path, $matches)) {
                    // Extract parameters - only keep named captures (non-numeric keys)
                    $params = array_filter($matches, function($key) {
                        return !is_numeric($key);
                    }, ARRAY_FILTER_USE_KEY);
                    
                    // for component routes
                    if (is_string($callback)) {
                        return $this->render_view($callback);
                    }

                    // Merge query parameters (GET) into path parameters
                    if ($method === 'GET') {
                        $params = array_merge($params, $queryParams);
                    }
                        
                    if (is_array($callback)) {
                        $controller = new $callback[0]();
                        $method = $callback[1];
                        
                        // Handle differently for POST requests
                        if ($this->request->getMethod() === 'POST') {
                            // Pass both params and request to the controller method
                            return $controller->$method($params, $this->request);
                        } else {
                            // For GET requests, maintain the existing behavior
                            return $controller->$method($params);
                        }
                    }
                    
                    // For closure callbacks
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
        $viewPath = Application::$ROOT_DIR . "/views/$view";
        
        if (file_exists($viewPath)) {
            // Capture the output
            ob_start();
            include $viewPath;
            $content = ob_get_clean();
            return $content;
        } else {
            return "<div class='error'>View '$view' not found</div>";
        }
    }
}