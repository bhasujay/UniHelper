<?php

namespace app\core;

class Router
{
    protected array $routes = [
        'GET' => [
            '/home' => 'home.html',
            '/register' => ['AuthController', 'populateRegisterForm'],
            '/login' => 'login.php',
            '/logout' => ['AuthController', 'logout'],
            '/api' => ['apiGateway', 'handleRequest'],
            '/' => ['DashboardController', 'index'],
            '/dashboard' => ['DashboardController', 'index'],
            '/profile/view' => ['DashboardController', 'profileIndex'],
            '/profile/edit' => ['DashboardController', 'profileIndex'],
            '/api/sessions' => ['SessionController', 'getAllSessions'],
            '/api/sessions/my' => ['SessionController', 'getMyessions'],
        ],
        'POST' => [
            '/register' => ['AuthController', 'register'],
            '/login' => ['AuthController', 'login'],
            '/api' => ['apiGateway', 'handleRequest'],
            '/profile/update' => ['DashboardController', 'profileUpdate'],
            '/api/sessions/delete' => ['SessionController', 'deleteSession'],
        ],
        'PUT' => [],
        'DELETE' => [],
        'DYNAMIC' => [
            '/:component' => ['DashboardController', 'renderComponent'],
            '/this/is/a/test/:id' => ['TestController', 'testMethod']
        ]
    ];
    public Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    // Resolves the route based on the request method and path
    public function resolve()
    {
        $path = $this->request->getPath();
        $method = $this->request->getMethod();
        
        // First check for exact match
        if (isset($this->routes[$method][$path])) {
            $callback = $this->routes[$method][$path];
            
            // if the callback is a string, render the view
            if (is_string($callback)) {
                return $this->render_view($callback);
            }

            // if the callback is an array, instantiate the controller and call the method
            if (is_array($callback)) {
                require_once dirname(__DIR__,1) . '/controllers/' . $callback[0] . '.php';
                $controllerClass = 'app\\controllers\\' . $callback[0];
                $controller = new $controllerClass();
                $method = $callback[1];
                
                $refMethod = new \ReflectionMethod($controller, $method);
                if ($refMethod->getNumberOfParameters() > 0) {
                    return $controller->$method($this->request);
                } else {
                    return $controller->$method();
                }
            }
            
            // if the callback is a closure, call it directly
            return call_user_func($callback);
        }
        
        // Check DYNAMIC routes for pattern matching
        if (isset($this->routes['DYNAMIC'])) {
            foreach ($this->routes['DYNAMIC'] as $route => $callback) {
                if (strpos($route, ':') !== false) {
                    $routePattern = preg_replace('/:(\w+)/', '(?P<$1>[^/]+)', $route);
                    $routePattern = "#^$routePattern$#";
                    
                    if (preg_match($routePattern, $path, $matches)) {
                        // Extract parameters - only keep named captures
                        $params = array_filter($matches, function($key) {
                            return !is_numeric($key);
                        }, ARRAY_FILTER_USE_KEY);
                        
                        // if the callback is a string, render the view
                        if (is_string($callback)) {
                            return $this->render_view($callback);
                        }

                        // if the callback is an array, instantiate the controller and call the method
                        if (is_array($callback)) {
                            require_once dirname(__DIR__) . '/controllers/' . $callback[0] . '.php';
                            $controllerClass = 'app\\controllers\\' . $callback[0];
                            $controller = new $controllerClass();
                            $method = $callback[1];
                            
                            $refMethod = new \ReflectionMethod($controller, $method);
                            if ($refMethod->getNumberOfParameters() > 0) {
                                return $controller->$method($params);
                            } else {
                                return $controller->$method();
                            }
                        }
                        
                        // if the callback is a closure, call it directly
                        return call_user_func_array($callback, [$params]);
                    }
                }
            }
        }
        
        // Handle 404
        http_response_code(404);
        return $this->render_view('404.php');
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