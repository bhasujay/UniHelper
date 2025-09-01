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

        $callback = $this->routes[$method][$path] ?? false;

        if (!$callback)
        {
            // Handle 404 Not Found
            http_response_code(404);
            return "404 Not Found";
        }

        if (is_string($callback))
            return $this->render_view($callback);

        return call_user_func($callback);
    }

    public function render_view($view)
    {
        require_once Application::$ROOT_DIR . "/views/$view";
    }
}