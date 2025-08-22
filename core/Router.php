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

        // Handle static files (CSS, JS, images)
        if ($this->handleStaticFiles($path))
            return;
        
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
        
        // Optionally, you can include a layout file here
        
    }

    protected function handleStaticFiles($path)
    {
        // Define static file extensions and their MIME types
        $staticExtensions = 
        [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
            'ico' => 'image/x-icon'
        ];

        // Get file extension
        $pathInfo = pathinfo($path);
        $extension = $pathInfo['extension'] ?? '';

        if (!isset($staticExtensions[$extension]))
            return false; // Not a static file

        // Try to find the file in views directory
        $filePath = Application::$ROOT_DIR . "/views" . $path;

        if (file_exists($filePath))
        {
            // Set appropriate content type
            header('Content-Type: ' . $staticExtensions[$extension]);
            
            // Output the file
            readfile($filePath);
            return true;
        }

        return false; // File not found
    }

    
}